# Portfolio Projects

This repository contains personal projects by Snat. It only includes code I have written personally.

Most professional work is delivered through my business, Nerd @ Technologies (https://nerd-at-tech.co.uk). I do not claim work completed by others at the business; projects stored here are my own code.

## Projects

- **blog** — A lightweight, self-hosted blog application.

### Blog

What it is:

A minimal blog with a small admin area for creating and managing posts, categories, and tags.

How it's coded:

- Plain PHP (no framework) using server-side templates in the `templates/` directory.
- Storage: SQLite via PDO (initialised in `db/schema.php`).
- Authentication: password hashing (BCRYPT), sessions and CSRF tokens.
- Frontend: simple HTML/CSS with assets in `assets/` and optional JS in `assets/js/`.
- Admin UI: `admin/` folder for creating and editing posts, categories and tags.

Core files:

- [blog/config.php](blog/config.php) — Site constants and PDO/SQLite setup.
- [blog/functions.php](blog/functions.php) — Helper functions (auth, CSRF, helpers).
- [blog/db/schema.php](blog/db/schema.php) — Database schema and initialization.
- [blog/index.php](blog/index.php), [blog/post.php](blog/post.php) — Public-facing pages.
- [blog/admin/](blog/admin/) — Admin UI and post management.
- [blog/setup.php](blog/setup.php) — First-run admin creation (DELETE AFTER USE).
- [blog/templates/](blog/templates/) — Server-side templates.
- [blog/assets/](blog/assets/) — Styles, images and client-side JS.

Getting started (local):

1. Install PHP 7.4 or newer with the SQLite PDO extension (`pdo_sqlite`).
2. Ensure the `blog/db` directory is writable by PHP (the SQLite file `blog/db/blog.sqlite` is created automatically).
3. From the `blog` folder run the built-in server:

```bash
cd blog
php -S localhost:8000
```

4. Open `http://localhost:8000` in your browser. Use `setup.php` to create the initial admin account, then delete `setup.php` from the server for security.

If you want me to add a license, CI, or a CONTRIBUTING guide, tell me which license and I'll update the README.