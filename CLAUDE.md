# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a family cookbook site built on WordPress. It has two main components:

1. **`cookbook-recipes/`** — A custom WordPress Gutenberg block plugin (`cookbook/cookbook-recipes`) that lets editors add structured recipes to posts/pages.
2. **`import_recipes.py`** — A Python script that bulk-imports family recipes into WordPress via the WP Recipe Maker (WPRM) REST API.

## WordPress Dev Environment

The project uses `@wordpress/env` (wp-env) for local development, configured in `.wp-env.json`. It runs at `http://localhost:8888` and loads two plugins: `mediavine-create` (from wordpress.org) and the local `./cookbook-recipes` plugin.

```bash
# Start the local WordPress environment (from repo root)
npx wp-env start

# Stop it
npx wp-env stop
```

## Block Plugin Commands

All block commands run from `cookbook-recipes/`:

```bash
cd cookbook-recipes

npm start          # development build with watch
npm run build      # production build
npm run lint:js    # lint JavaScript
npm run lint:css   # lint SCSS/CSS
npm run format     # auto-format files
```

The build output goes to `cookbook-recipes/build/` (gitignored).

## Block Architecture

The block (`cookbook/cookbook-recipes`) is a static block — it uses `save.js` to serialize HTML into the database, not a server-side `render.php`. Block attributes stored in `block.json`:

- `recipeName`, `description`, `notes` — freeform RichText fields
- `prepTime`, `cookTime`, `servings` — plain text, edited via InspectorControls sidebar panel
- `ingredients` — RichText rendered as `<ul>` with `multiline="li"`
- `instructions` — RichText rendered as `<ol>` with `multiline="li"`

Source files live in `src/cookbook-recipes/`. The `view.js` file is a placeholder (only logs to console) and is not wired into `block.json` — it can be deleted if no front-end JS is needed.

## Recipe Importer

`import_recipes.py` talks to the WPRM REST API (`/wp-json/wprm/v1`) using HTTP Basic Auth with a WordPress application password. Recipes are hardcoded as a `RECIPES` list in the file. To run it, the wp-env environment must be running and the `import` application password must be configured in WP admin for the `admin` user.

```bash
python3 import_recipes.py
```
