# Portfolio Projects

This repository contains personal projects by Snat. It is intended to showcase code I've written personally and exist purely to demonstrate what I can do; they are not actively maintained or ongoing development work.

While I contribute the majority of the code for projects delivered through my business, Nerd @ Technologies (https://nerd-at-tech.co.uk), other people also contribute there. To avoid implying those business projects are solely my work, this repository includes only code I wrote personally.

You're welcome to fork and use the code as you like, but please let me know what you do with it (a link or shout-out would be appreciated). I will not accept pull/merge requests.

## Projects

- **blog** - A lightweight, self-hosted blog application.

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

- [blog/config.php](blog/config.php) - Site constants and PDO/SQLite setup.
- [blog/functions.php](blog/functions.php) - Helper functions (auth, CSRF, helpers).
- [blog/db/schema.php](blog/db/schema.php) - Database schema and initialization.
- [blog/index.php](blog/index.php), [blog/post.php](blog/post.php) - Public-facing pages.
- [blog/admin/](blog/admin/) - Admin UI and post management.
- [blog/setup.php](blog/setup.php) - First-run admin creation (DELETE AFTER USE).
- [blog/templates/](blog/templates/) - Server-side templates.
- [blog/assets/](blog/assets/) - Styles, images and client-side JS.

Getting started (local):

1. Install PHP 7.4 or newer with the SQLite PDO extension (`pdo_sqlite`).
2. Ensure the `blog/db` directory is writable by PHP (the SQLite file `blog/db/blog.sqlite` is created automatically).
3. From the `blog` folder run the built-in server:

```bash
cd blog
php -S localhost:8000
```

4. Open `http://localhost:8000` in your browser. Use `setup.php` to create the initial admin account, then delete `setup.php` from the server for security.

## Other Modules

This repository also contains several additional small, self-contained PHP modules. Each is intentionally minimal (no framework) and uses SQLite for storage where applicable. See each module's README for more details.

- **calender** - Event calendar and scheduling; includes ICS import/export and admin UI. See [calender/README.md](calender/README.md).
- **cms** - Simple content management system for pages, users and modules. See [cms/README.md](cms/README.md).
- **forum** - Threaded discussion forum with categories and moderation tools. See [forum/README.md](forum/README.md).
- **store** - Lightweight store with cart and checkout; includes PayPal integration. See [store/README.md](store/README.md).

### Calender

What it is:

A lightweight event calendar with simple admin UI, import/export and token-based APIs.

How it's coded:

- Plain PHP with SQLite (see `calender/db/schema.php`) and server-side templates in `calender/templates/`.
- Admin UI in `calender/admin/` and core helpers in `calender/core/`.

Core files:

- [calender/config.php](calender/config.php)
- [calender/functions.php](calender/functions.php)
- [calender/db/schema.php](calender/db/schema.php)
- [calender/index.php](calender/index.php), [calender/event.php](calender/event.php)
- [calender/admin/](calender/admin/) and [calender/templates/](calender/templates/)
- [calender/setup.php](calender/setup.php) — initial demo data (DELETE AFTER USE).

Getting started (local):

1. PHP 7.4+ with `pdo_sqlite`.
2. cd calender && php -S localhost:8001
3. Open http://localhost:8001 and run `setup.php` if you need demo data; remove `setup.php` afterwards.

### CMS

What it is:

A small CMS for static-style page management, roles and simple modules.

Core files:

- [cms/config.php](cms/config.php)
- [cms/core/database.php](cms/core/database.php)
- [cms/index.php](cms/index.php), [cms/page.php](cms/page.php)
- [cms/admin/](cms/admin/) and [cms/templates/](cms/templates/)

Getting started:

1. PHP 7.4+ with `pdo_sqlite`.
2. cd cms && php -S localhost:8002
3. Follow [cms/README.md](cms/README.md) for setup instructions.

### Forum

What it is:

A simple threaded forum with categories, threads and posts.

Core files:

- [forum/config.php](forum/config.php)
- [forum/functions.php](forum/functions.php)
- [forum/index.php](forum/index.php), [forum/thread.php](forum/thread.php)
- [forum/admin/](forum/admin/) and [forum/templates/](forum/templates/)

Getting started:

1. PHP 7.4+ with `pdo_sqlite`.
2. cd forum && php -S localhost:8003
3. See [forum/README.md](forum/README.md) for details.

### Store

What it is:

A minimal e-commerce module with cart, checkout and payment hooks.

Core files:

- [store/config.php](store/config.php)
- [store/functions.php](store/functions.php)
- [store/index.php](store/index.php), [store/product.php](store/product.php)
- [store/payments/](store/payments/) and [store/templates/](store/templates/)

Getting started:

1. PHP 7.4+ with `pdo_sqlite`.
2. cd store && php -S localhost:8004
3. Read [store/README.md](store/README.md) and remove any `setup.php` files after use.