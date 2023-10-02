# Forum

A lightweight, modern forum system for discussions, support threads, and community interaction.
Built with **PHP 8+**, **PDO + SQLite**, and a responsive white / orange design that matches the portfolio blog.

**Author:** Snat  
**Site:** https://terra.me.uk

---

## Quick Start

1. Run '/setup.php' and delete file.
2. Upload the `forum/` folder to your web server.
3. Ensure the `db/` directory and `assets/images/` are writable by the web server user.
4. Visit any public page (for example `/index.php`) to initialise the SQLite database and seed the default roles.
5. Register a new account at `/register.php`.


## Features

- Categories listing with thread and post counts
- Paginated thread lists per category
- Thread view with replies, author info, timestamps
- Reply form for logged-in users and soft-delete for posts
- User registration, login, and secure password handling
- Basic roles: `admin` and `user` (roles seeded on first run)
- Admin panel to manage categories, threads, posts and users
- CSRF protection, session hardening, and prepared statements throughout
- Optional search and profile pages

## Folder Structure

```
forum/
├── index.php               # Categories homepage
├── category.php            # Threads list (by category)
├── thread.php              # Thread view with posts and reply form
├── create_thread.php       # New thread form
├── login.php               # Log in
├── register.php            # User registration
├── logout.php              # Log out
├── config.php              # DB connection, constants
├── functions.php           # Shared functions, auth helpers
├── .htaccess               # Optional rewrites and basic headers
│
├── admin/
│   ├── index.php           # Dashboard
│   ├── categories.php      # Manage categories
│   ├── threads.php         # Moderate threads
│   ├── posts.php           # Moderate posts
│   ├── users.php           # Manage users
│   └── auth.php            # Admin auth helpers
│
├── assets/
│   ├── css/style.css       # Forum styling (white + orange)
│   ├── js/main.js          # Optional JS (nav, alerts, counters)
│   └── images/             # Avatars and uploaded images
│
├── db/
│   └── forum.sqlite        # SQLite database (auto-created)
│   └── schema.php          # CREATE TABLE statements (auto-run)
│
└── templates/
    ├── header.php
    ├── footer.php
    ├── category_card.php
    ├── thread_row.php
    ├── post_item.php
    └── admin_nav.php
```

## Database

- Uses SQLite via PDO. The file is `db/forum.sqlite` and is created automatically on first request.
- Schema is initialised by `db/schema.php` and includes the following tables: `roles`, `users`, `categories`, `threads`, `posts`.
- The `roles` table is seeded with `admin` and `user` on first run.

## Security

- All SQL queries use PDO prepared statements to prevent SQL injection.
- Passwords are hashed with `password_hash()` (bcrypt).
- CSRF tokens are used for all POST forms.
- Session regeneration is performed on login to prevent session fixation.
- Output is escaped via `htmlspecialchars()` helper to reduce XSS risk.
- `.htaccess` contains rules to block direct access to sensitive files and to provide basic security headers when used under Apache.

## Clean URLs

This project includes a sample `.htaccess` for Apache `mod_rewrite` support. Clean URL rewrites are optional; the application functions using query parameters out of the box.
