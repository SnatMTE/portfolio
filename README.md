# Portfolio

A collection of small PHP applications built without a framework: PHP 8,
PDO + SQLite and hand-written CSS.

## Projects

- **blog** — posts, categories, tags, RSS and pretty URLs
- **forum** — categories, threads, replies, profiles and moderation
- **store** — catalogue, session cart, PayPal checkout and order admin
- **cms** — shared authentication, roles and a module loader
- **calendar** — month grid, event management, ICS import/export and sync feeds

### calendar

A lightweight scheduling module.

- Monthly calendar grid with quick-add
- Event creation and editing with public/private visibility
- iCalendar import and export
- Token-protected sync feeds for Google Calendar, Apple Calendar and Outlook

## Running locally

```bash
cd blog     && php -S localhost:8000
cd forum    && php -S localhost:8001
cd store    && php -S localhost:8002
cd cms      && php -S localhost:8003
cd calendar && php -S localhost:8004
```

Each project still runs standalone, and each one can be dropped inside the CMS
to share authentication.
