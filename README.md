# Portfolio

A collection of small PHP applications built without a framework: PHP 8,
PDO + SQLite and hand-written CSS.

## Projects

- **blog** — posts, categories, tags, RSS and pretty URLs
- **forum** — categories, threads, replies, profiles and moderation
- **store** — catalogue, session cart, PayPal checkout and order admin
- **cms** — shared authentication, roles and a module loader
- **calendar** — month grid, event management, ICS import/export and sync feeds
- **dlm** — download manager with secure file delivery

### dlm

A small file distribution module.

- Public listing with search and category filters
- Files streamed through PHP, never linked directly
- MIME validation on upload and token-based download links
- Admin area for uploading, editing and deleting files

## Running locally

```bash
cd blog     && php -S localhost:8000
cd forum    && php -S localhost:8001
cd store    && php -S localhost:8002
cd cms      && php -S localhost:8003
cd calendar && php -S localhost:8004
cd dlm      && php -S localhost:8005
```

Each project still runs standalone, and each one can be dropped inside the CMS
to share authentication.
