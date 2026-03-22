#!/usr/bin/env python3
"""
Creates WordPress posts for each recipe using the cookbook/cookbook-recipes block.
Imports recipe data from import_recipes.py.

Requirements: the wp-env environment must be running (npx wp-env start).

Usage:
    python3 create_recipe_pages.py
"""

import html
import json
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
API_BASE    = f"{WP_URL}/wp-json/wp/v2"
# ──────────────────────────────────────────────────────────────────────────────

AUTH    = HTTPBasicAuth(WP_USER, WP_APP_PASS)
HEADERS = {"Content-Type": "application/json", "Accept": "application/json"}


# ── Helpers ────────────────────────────────────────────────────────────────────

def get_or_create_category(name: str) -> int:
    """Return the WP category ID for name, creating it if needed."""
    resp = requests.get(
        f"{API_BASE}/categories",
        params={"search": name, "per_page": 20},
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()
    for cat in resp.json():
        if cat["name"].lower() == name.lower():
            return cat["id"]

    resp = requests.post(
        f"{API_BASE}/categories",
        json={"name": name},
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()
    cat_id = resp.json()["id"]
    print(f"  Created category '{name}' (id={cat_id})")
    return cat_id


def li_items(lines: list) -> str:
    """Wrap each line in <li> tags for use as a RichText multiline value."""
    return "".join(f"<li>{html.escape(line.strip())}</li>" for line in lines if line.strip())


def build_block(recipe: dict) -> str:
    """
    Produce serialized Gutenberg block markup for cookbook/cookbook-recipes.

    Attribute storage mirrors what save.js serialises:
      - ingredients / instructions: inner HTML of the <ul>/<ol> (one <li> per item)
      - all other fields: plain strings
    """
    name         = recipe.get("name", "")
    description  = recipe.get("description", "")
    prep_time    = recipe.get("prep_time", "")
    cook_time    = recipe.get("cook_time", "")
    servings     = str(recipe.get("servings", "")) if recipe.get("servings") else ""
    notes        = recipe.get("notes", "")
    ingredients  = li_items(recipe.get("ingredients", []))
    instructions = li_items(recipe.get("instructions", []))

    attrs = {
        "recipeName":  name,
        "description": description,
        "prepTime":    str(prep_time) if prep_time else "",
        "cookTime":    str(cook_time) if cook_time else "",
        "servings":    servings,
        "ingredients": ingredients,
        "instructions": instructions,
        "notes":       notes,
    }

    # Build the saved HTML that save.js produces.
    meta_html = ""
    if prep_time or cook_time or servings:
        parts = []
        if prep_time:
            parts.append(f"<span><strong>Prep:</strong> {html.escape(str(prep_time))}</span>")
        if cook_time:
            parts.append(f"<span><strong>Cook:</strong> {html.escape(str(cook_time))}</span>")
        if servings:
            parts.append(f"<span><strong>Serves:</strong> {html.escape(servings)}</span>")
        meta_html = f'<div class="recipe-meta">{"".join(parts)}</div>'

    notes_html = f'<p class="recipe-notes">{html.escape(notes)}</p>' if notes else ""

    inner_html = (
        f'<h2 class="recipe-name">{html.escape(name)}</h2>'
        f'<p class="recipe-description">{html.escape(description)}</p>'
        f"{meta_html}"
        f"<h3>Ingredients</h3>"
        f'<ul class="recipe-ingredients">{ingredients}</ul>'
        f"<h3>Instructions</h3>"
        f'<ol class="recipe-instructions">{instructions}</ol>'
        f"{notes_html}"
    )

    block_comment = f"<!-- wp:cookbook/cookbook-recipes {json.dumps(attrs, ensure_ascii=False)} -->"
    block_div     = f'<div class="wp-block-cookbook-cookbook-recipes cookbook-recipe">{inner_html}</div>'
    block_close   = "<!-- /wp:cookbook/cookbook-recipes -->"

    return f"{block_comment}\n{block_div}\n{block_close}"


def post_exists(title: str) -> bool:
    """Return True if a published post with this exact title already exists."""
    resp = requests.get(
        f"{API_BASE}/posts",
        params={"search": title, "per_page": 20, "status": "publish"},
        auth=AUTH,
        headers=HEADERS,
    )
    resp.raise_for_status()
    return any(p["title"]["rendered"] == title for p in resp.json())


# ── Main ───────────────────────────────────────────────────────────────────────

def main():
    print(f"Connecting to WordPress at {WP_URL}…")
    try:
        requests.get(f"{API_BASE}/posts", auth=AUTH, timeout=5).raise_for_status()
    except Exception as exc:
        print(f"ERROR: Cannot reach WordPress — {exc}")
        print("Make sure wp-env is running: npx wp-env start")
        sys.exit(1)

    category_cache: dict[str, int] = {}
    created = skipped = 0

    for recipe in RECIPES:
        name = recipe.get("name", "Untitled Recipe")

        if post_exists(name):
            print(f"  SKIP  {name!r} (already exists)")
            skipped += 1
            continue

        # Resolve category.
        cat_name = recipe.get("category", "")
        if cat_name:
            if cat_name not in category_cache:
                category_cache[cat_name] = get_or_create_category(cat_name)
            cat_ids = [category_cache[cat_name]]
        else:
            cat_ids = []

        post_body = {
            "title":      name,
            "content":    build_block(recipe),
            "status":     "publish",
            "categories": cat_ids,
        }

        resp = requests.post(
            f"{API_BASE}/posts",
            json=post_body,
            auth=AUTH,
            headers=HEADERS,
        )
        if resp.status_code in (200, 201):
            post = resp.json()
            print(f"  OK    {name!r}  →  {post['link']}")
            created += 1
        else:
            print(f"  FAIL  {name!r}  status={resp.status_code}  {resp.text[:120]}")

    print(f"\nDone — {created} created, {skipped} skipped.")


if __name__ == "__main__":
    main()
