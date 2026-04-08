# Portfolio Blog

A clean, personal portfolio blog built with **PHP 8+**, **PDO + SQLite**, and a white/orange responsive design.

**Author:** Snat  
**Site:** https://terra.me.uk

---

## Quick Start

1. Upload the `blog/` folder to your web server.
2. Navigate to `/setup.php` and create your admin account.
3. **Delete `setup.php` immediately** after creating the account.
4. Log in at `/admin/` and start writing!

## Folder Structure

```
blog/
├── index.php            # Blog listing / search / category / tag archive
├── post.php             # Single post view
├── login.php            # Admin login
├── logout.php           # Admin logout
├── rss.php              # RSS 2.0 feed (/rss)
├── setup.php            # First-run admin account setup (DELETE AFTER USE)
├── config.php           # DB connection & site constants
├── functions.php        # All reusable PHP functions
├── .htaccess            # Clean URLs & security headers
│
├── admin/
│   ├── auth.php         # Session guard + flash messages
│   ├── index.php        # Dashboard
│   ├── posts.php        # List / delete posts
│   ├── create_post.php  # Create post (TinyMCE editor)
│   ├── edit_post.php    # Edit post
│   ├── categories.php   # Manage categories
│   └── tags.php         # Manage tags
│
├── assets/
│   ├── css/style.css    # Main stylesheet (Inter font, white + orange)
│   ├── js/main.js       # Vanilla JS (nav toggle, alerts, confirm)
│   └── images/uploads/  # Uploaded featured images (auto-created)
│
├── db/
│   ├── schema.php       # CREATE TABLE statements (auto-runs on each request)
│   └── blog.sqlite      # SQLite database (auto-created on first request)
│
└── templates/
    ├── header.php       # HTML <head> + site nav
    ├── footer.php       # Site footer + JS script tag
    ├── post_card.php    # Post preview card
    └── admin_nav.php    # Admin sidebar nav
```

## Security Notes

- All DB queries use **PDO prepared statements**.
- Passwords hashed with `password_hash()` (bcrypt, cost 12).
- CSRF tokens on every POST form.
- Session cookie: `httponly`, `samesite=Strict`.
- `.htaccess` blocks direct access to `config.php`, `functions.php`, and `db/`.

## Clean URLs (requires `mod_rewrite`)

| URL                   | Maps to                        |
|-----------------------|--------------------------------|
| `/post/my-slug`       | `post.php?slug=my-slug`        |
| `/category/php`       | `index.php?category=php`       |
| `/tag/tutorial`       | `index.php?tag=tutorial`       |
| `/search?q=keyword`   | `index.php?q=keyword`          |
| `/rss`                | `rss.php`                      |
