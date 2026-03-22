#!/usr/bin/env python3
"""
Creates Mediavine Create recipe cards and associated WordPress posts
for each recipe in import_recipes.py.

Requirements: the wp-env environment must be running (npx wp-env start).

Usage:
    python3 create_recipe_pages.py
"""

import html
import sys
from requests.auth import HTTPBasicAuth
import requests

# ── Import recipe data from existing importer ──────────────────────────────────
sys.path.insert(0, ".")
from import_recipes import RECIPES  # noqa: E402

# ── Configuration ──────────────────────────────────────────────────────────────
WP_URL      = "http://localhost:8888"
WP_USER     = "admin"
WP_APP_PASS = "k5ZYfuRP5bGQpl6pePvz2ZF5"   # Application password from WP admin
MV_API      = f"{WP_URL}/wp-json/mv-create/v1"
WP_API      = f"{WP_URL}/wp-json/wp/v2"
# ──────────────────────────────────────────────────────────────────────────────

AUTH    = HTTPBasicAuth(WP_USER, WP_APP_PASS)
HEADERS = {"Content-Type": "application/json", "Accept": "application/json"}

MINUTE_IN_SECONDS = 60


# ── Helpers ────────────────────────────────────────────────────────────────────

def get_or_create_category(name: str) -> int:
    """Return the WP category ID for name, creating it if needed."""
    resp = requests.get(
        f"{WP_API}/categories",
        params={"search": name, "per_page": 20},
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()
    for cat in resp.json():
        if cat["name"].lower() == name.lower():
            return cat["id"]

    resp = requests.post(
        f"{WP_API}/categories",
        json={"name": name},
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()
    cat_id = resp.json()["id"]
    print(f"  Created category '{name}' (id={cat_id})")
    return cat_id


def card_exists(title: str) -> bool:
    """Return True if a Mediavine Create recipe card with this title already exists."""
    resp = requests.get(
        f"{MV_API}/creations",
        params={"search": title, "type": "recipe", "per_page": 50},
        auth=AUTH,
        headers=HEADERS,
    )
    if resp.status_code != 200:
        return False
    payload = resp.json()
    # MC API wraps results in a 'data' key
    items = payload.get("data", payload) if isinstance(payload, dict) else payload
    if not isinstance(items, list):
        return False
    return any(c.get("title", "").lower() == title.lower() for c in items)


def create_card(recipe: dict, category_id: int | None) -> dict:
    """POST a new Mediavine Create recipe card and return the created object."""
    prep_min = recipe.get("prep_time") or 0
    cook_min = recipe.get("cook_time") or 0
    servings = recipe.get("servings", "")

    steps = recipe.get("instructions", [])
    instructions_html = (
        "<ol>" + "".join(f"<li>{html.escape(s.strip())}</li>" for s in steps if s.strip()) + "</ol>"
        if steps else ""
    )

    body = {
        "title":        recipe["name"],
        "type":         "recipe",
        "description":  recipe.get("description", ""),
        "instructions": instructions_html,
        "notes":        recipe.get("notes", ""),
        "yield":        str(servings) if servings else "",
    }
    if prep_min:
        body["prep_time"]   = int(prep_min) * MINUTE_IN_SECONDS
    if cook_min:
        body["active_time"] = int(cook_min) * MINUTE_IN_SECONDS
    if category_id:
        body["category"] = category_id

    resp = requests.post(f"{MV_API}/creations", json=body, auth=AUTH, headers=HEADERS)
    resp.raise_for_status()
    payload = resp.json()
    return payload.get("data", payload) if isinstance(payload, dict) and "data" in payload else payload


def set_ingredients(card_id: int, ingredients: list) -> None:
    """Set ingredient supplies for a creation card."""
    supplies = [
        {"original_text": ing.strip(), "type": "ingredient", "position": i}
        for i, ing in enumerate(ingredients)
        if ing.strip()
    ]
    if not supplies:
        return

    resp = requests.post(
        f"{MV_API}/creations/{card_id}/supplies",
        json={"id": card_id, "type": "ingredient", "data": supplies},
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()


def create_post(card_id: int, title: str, category_ids: list) -> dict:
    """
    Create a published WordPress post embedding the Mediavine Create shortcode.
    Saving the post triggers the plugin's post_updated hook, which automatically
    links the card to the post (sets associated_posts and canonical_post_id).
    """
    shortcode = f'[mv_create key="{card_id}" type="recipe" title="{title}"]'
    resp = requests.post(
        f"{WP_API}/posts",
        json={
            "title":      title,
            "content":    shortcode,
            "status":     "publish",
            "categories": category_ids,
        },
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()
    return resp.json()


# ── Main ───────────────────────────────────────────────────────────────────────

def main():
    print(f"Connecting to WordPress at {WP_URL}…")
    try:
        requests.get(f"{WP_API}/posts", auth=AUTH, timeout=5).raise_for_status()
    except Exception as exc:
        print(f"ERROR: Cannot reach WordPress — {exc}")
        print("Make sure wp-env is running: npx wp-env start")
        sys.exit(1)

    category_cache: dict[str, int] = {}
    created = skipped = 0

    for recipe in RECIPES:
        name = recipe.get("name", "Untitled Recipe")

        if card_exists(name):
            print(f"  SKIP  {name!r} (already exists)")
            skipped += 1
            continue

        cat_name = recipe.get("category", "")
        cat_id   = None
        cat_ids  = []
        if cat_name:
            if cat_name not in category_cache:
                category_cache[cat_name] = get_or_create_category(cat_name)
            cat_id  = category_cache[cat_name]
            cat_ids = [cat_id]

        try:
            card    = create_card(recipe, cat_id)
            card_id = card["id"]

            set_ingredients(card_id, recipe.get("ingredients", []))

            post = create_post(card_id, name, cat_ids)
            print(f"  OK    {name!r}  →  {post['link']}")
            created += 1

        except Exception as exc:
            print(f"  FAIL  {name!r}  {exc}")

    print(f"\nDone — {created} created, {skipped} skipped.")


if __name__ == "__main__":
    main()
