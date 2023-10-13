# Portfolio

A collection of small PHP applications I maintain to keep my core web
development skills sharp. No frameworks, no build step: PHP 8, PDO + SQLite and
hand-written CSS throughout.

## Projects

### blog

A small blogging platform with an admin area.

- Posts, categories and tags
- BCRYPT password hashing and CSRF protection on every form
- Pretty URLs with a query-string fallback
- RSS feed

### forum

A threaded discussion forum built on the same patterns as the blog.

- Categories, threads and replies
- Public registration and user profiles
- Basic moderation tools in the admin area
- Full-text style search across threads and posts

## Running locally

```bash
cd blog   && php -S localhost:8000
cd forum  && php -S localhost:8001
```

Each project is self-contained. Run `setup.php` once to create the database and
admin account, then remove it.
