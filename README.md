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
- **crm** — customers, companies, leads, tasks and internal messaging

### crm

A small customer relationship management module.

- Customers and companies with full CRUD and CSV export
- Lead pipeline with stage tracking
- Tasks, notes, internal messages and an activity log
- JSON endpoints for autocomplete and dashboard stats

## Running locally

```bash
cd blog     && php -S localhost:8000
cd forum    && php -S localhost:8001
cd store    && php -S localhost:8002
cd cms      && php -S localhost:8003
cd calendar && php -S localhost:8004
cd dlm      && php -S localhost:8005
cd crm      && php -S localhost:8006
```

Each project still runs standalone, and each one can be dropped inside the CMS
to share authentication.
