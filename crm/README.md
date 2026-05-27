# CRM Module

A lightweight Customer Relationship Management system built in PHP 8+ with SQLite, designed to integrate seamlessly with the existing site ecosystem.

## Features

- **Customers** — full CRUD, search, status filter, CSV export, tags
- **Companies** — full CRUD, linked customers and leads
- **Leads** — pipeline view (New → Won/Lost), stage tabs, deal values, CSV export
- **Tasks** — status/priority/overdue filtering, linked to any entity
- **Notes** — quick notes on any customer, lead, or company; private flag
- **Messages** — internal user-to-user messaging with reply support
- **Activity Log** — full audit trail with user, entity, and date filtering
- **API endpoints** — JSON autocomplete for customers and leads; stats endpoint
- **Demo mode** — place a `DEMO` file in the module root for sample data

## Requirements

- PHP 8.0+
- Extensions: `pdo_sqlite`, `mbstring`, `filter`
- CMS module loaded (for auth, helpers, and shared styles)
- Writable `db/` directory

## Installation

1. Copy the `crm/` folder to your site root (alongside `cms/`, `blog/`, etc.).
2. Ensure `crm/db/` is writable by your web user.
3. Visit `/crm/setup.php` once to initialise the database.
4. **Delete or restrict `setup.php`** after setup.
5. Log in via the existing CMS login — CRM inherits your session and role.

### Demo data

To load sample companies, customers, leads, and tasks automatically:

1. Ensure the `DEMO` file exists in `crm/` (it does by default in this repo).
2. Run `setup.php` — the seed runs on first connection.
3. Remove `DEMO` when you are done.

## Permissions

| Role    | Access                              |
|---------|-------------------------------------|
| Admin   | Full access including delete        |
| Editor  | Create, read, update; no delete     |
| Any user| Read-only (own assigned records)    |

Roles are inherited from the CMS `users` table (`role` column).

## Database

SQLite file stored at `crm/db/crm.sqlite`. Tables:

| Table                | Purpose                              |
|----------------------|--------------------------------------|
| `crm_companies`      | Company accounts                     |
| `crm_customers`      | Individual contacts                  |
| `crm_leads`          | Sales pipeline entries               |
| `crm_tasks`          | Action items linked to any entity    |
| `crm_notes`          | Free-text notes on any entity        |
| `crm_messages`       | Internal user-to-user messages       |
| `crm_tags`           | Tag vocabulary                       |
| `crm_taggables`      | Polymorphic tag assignments          |
| `crm_attachments`    | File attachment metadata (future)    |
| `crm_activity_log`   | Full audit trail                     |
| `crm_email_log`      | Outbound email log (future)          |
| `crm_follow_ups`     | Scheduled follow-up reminders        |

### MySQL migration

The schema uses `TEXT` for datetimes and `INTEGER PRIMARY KEY AUTOINCREMENT`. To migrate:

1. Swap `TEXT` → `DATETIME` on all timestamp columns.
2. Swap `INTEGER PRIMARY KEY AUTOINCREMENT` → `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.
3. Change the DSN in `core/database.php` to a MySQL connection.

## API

| Endpoint                     | Method | Description                       |
|------------------------------|--------|-----------------------------------|
| `/crm/api/stats.php`         | GET    | Dashboard stats as JSON           |
| `/crm/api/customers.php?q=`  | GET    | Customer autocomplete search      |
| `/crm/api/leads.php?q=`      | GET    | Lead autocomplete search          |
| `/crm/api/leads.php`         | POST   | Update lead stage (`{id, stage}`) |

All endpoints require an active CMS session.

## File structure

```
crm/
├── activity.php          Activity log page
├── config.php            Bootstrap — constants and includes
├── dashboard.php         Dashboard widgets
├── functions.php         Single-file include (loads config)
├── index.php             Entry point (→ dashboard)
├── module.php            CMS module manifest
├── setup.php             First-run setup (delete after use)
├── DEMO                  Triggers demo seed on first run
├── api/
│   ├── customers.php
│   ├── leads.php
│   └── stats.php
├── assets/
│   ├── css/crm.css
│   └── js/crm.js
├── companies/            index, create, view, edit, delete, export
├── core/
│   ├── database.php      getCRMDB() singleton
│   └── helpers.php       All utility functions
├── customers/            index, create, view, edit, delete, export
├── db/
│   ├── demo_seed.php     seedDemoCRM()
│   └── schema.php        initCRMSchema()
├── includes/
│   └── auth.php          Permission guards
├── leads/                index, create, view, edit, delete, export
├── messages/             index, view, compose, delete
├── notes/                create, delete
├── tasks/                index, create, edit, delete
└── templates/
    ├── footer.php
    └── header.php
```

## Security notes

- All user input is passed through named PDO prepared statements.
- CSRF tokens (from `crmCsrfToken()` / `crmValidateCsrf()`) are required on all state-changing requests.
- Output is escaped with `htmlspecialchars(…, ENT_QUOTES)` throughout.
- Access control is enforced via `requireCRMAccess()`, `requireCRMEditor()`, and `requireCRMAdmin()` on every page.
- The `db/` directory should not be publicly accessible — configure your web server to deny requests to `*.sqlite` files.
