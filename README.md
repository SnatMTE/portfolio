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

A minimal e-commerce system with a product catalogue, session cart, PayPal
checkout and an admin area for products, orders and settings.

### cms

A modular content management system that acts as the hub for the other projects.

- Shared user authentication, roles and sessions
- Module loader that discovers `module.php` manifests automatically
- Page management and site settings
- Modules placed inside `cms/` inherit the CMS session and database

## Running locally

```bash
cd blog   && php -S localhost:8000
cd forum  && php -S localhost:8001
cd store  && php -S localhost:8002
cd cms    && php -S localhost:8003
```

Each project still runs standalone. Run `setup.php` once per application, then
remove it.
