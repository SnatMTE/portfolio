# Portfolio

A place for the small PHP projects I build while working through the language
properly: no frameworks, no build step, just PHP, SQLite and hand-written CSS.

## Projects

### blog

A small blogging platform with an admin area for posts, a public listing and a
single-post view.

- PHP 8, PDO + SQLite
- BCRYPT password hashing
- Server-rendered templates and custom CSS

## Running locally

```bash
cd blog
php -S localhost:8000
```

Copy the folder to a web server and point PHP at it; the SQLite database and its
tables are created on first run.
