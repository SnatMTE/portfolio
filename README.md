# Portfolio

A collection of small PHP applications built without a framework: PHP 8,
PDO + SQLite and hand-written CSS.

## Projects

### blog

A blogging platform with an admin area for posts, categories and tags, an RSS
feed and pretty URLs.

### forum

A threaded discussion forum with categories, replies, profiles, search and
basic moderation.

### store

A minimal e-commerce system.

- Product listing with category filters and search
- Session-based cart (no login required)
- Checkout with PayPal REST API v2 (sandbox and live modes)
- Admin area for products, orders and store settings

## Running locally

```bash
cd blog   && php -S localhost:8000
cd forum  && php -S localhost:8001
cd store  && php -S localhost:8002
```

Each project is self-contained. Run `setup.php` once to create the database and
admin account, then remove it.

PayPal credentials are read from environment variables so nothing sensitive is
committed.
