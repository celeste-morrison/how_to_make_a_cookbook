# 🍳 The Morrison Family Cookbook

A local WordPress site for managing and sharing our family recipes.

## What's in here

- **WordPress** running locally via Docker (`@wordpress/env`)
- **[Create plugin](https://wordpress.org/plugins/mediavine-create/)** — beautiful recipe cards with nutrition info, SEO schema, and more
- **`cookbook-recipes` plugin** — a custom Gutenberg block for simple recipe entries

---

## Prerequisites

Make sure these are installed before you start:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (must be running)
- [Node.js](https://nodejs.org/) (v18+)

---

## First-time setup

```bash
# In this project directory:
npm install
npm start
```

This will download WordPress + all plugins into Docker containers. It takes a few minutes the first time.

Once it's done, visit:
- **Site:** http://localhost:8888
- **Admin:** http://localhost:8888/wp-admin (user: `admin`, password: `password`)

---

## Daily use

```bash
npm start     # Start the site
npm stop      # Stop the site (saves your data)
npm run logs  # View WordPress logs
```

---

## Adding recipes

1. Go to http://localhost:8888/wp-admin
2. Go to **Create → Add New**
3. Fill in the recipe name, ingredients, instructions, servings, etc.
4. Hit **Publish** — it'll show up on the site with a beautiful recipe card

---

## Letting your spouse access it on the home network

WordPress stores its URL in the database, so to let another device on your home WiFi access the site:

1. Find your Mac's local IP address:
   ```bash
   ipconfig getifaddr en0
   ```
   It'll look like `192.168.1.42`

2. Go to **WP Admin → Settings → General** and change both:
   - WordPress Address: `http://192.168.1.42:8888`
   - Site Address: `http://192.168.1.42:8888`

3. Hit **Save** — now anyone on the same WiFi can open `http://192.168.1.42:8888`

> **Note:** Do this step each time your IP changes (e.g. after restarting your router). You can also assign a static IP to your Mac in your router's DHCP settings.

---

## Building the custom block (optional)

If you want to edit the custom `cookbook-recipes` Gutenberg block:

```bash
cd cookbook-recipes
npm install
npm start      # watch mode for development
npm run build  # production build
```

---

## Starting fresh

If you want to wipe the database and start over:

```bash
npm run destroy
npm start
```
