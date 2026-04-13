# Full Stack PHP Developer Portfolio

A collection of small, self-built web applications demonstrating full‑stack development using PHP, SQLite, and vanilla frontend technologies.

These projects focus on:

- Backend system design
- Secure authentication patterns
- Database-driven applications
- Modular architecture without frameworks
- Practical PHP engineering and system building

> These projects are provided for demonstration purposes and are not actively maintained.

## Purpose

This repository demonstrates practical engineering ability outside of frameworks, focusing on core web development skills, security awareness, and modular architecture.

## Skills Demonstrated

- Full stack PHP development without frameworks
- SQLite schema design and PDO usage
- Authentication systems (sessions, password hashing, CSRF protection)
- Modular application architecture and module loading
- Server-side rendering and templating
- Basic web security principles (input validation, password hashing)
- Simple API-style endpoints and structured backend design
- Reusable patterns across several small applications

## System Architecture

- Clear separation of configuration, logic, and presentation
- Shared helper patterns across applications
- SQLite for portability and simplicity
- Server-side rendered output for predictable behaviour
- Minimal external dependencies
- Consistent folder and file structure across projects

## Shared CMS Integration

All modules can be integrated into the CMS system to share authentication, session handling and access control. The CMS acts as a central hub for:

- Shared user authentication and sessions
- Role-based access control
- Module-based loader and unified admin

## Projects Overview

- **blog** — Secure blogging platform with admin management
- **calender** — Event scheduling system with import and export features
- **cms** — Central content management system with shared user base
- **forum** — Threaded discussion forum with moderation tools
- **store** — Minimal e‑commerce system with cart and checkout

### blog

A secure, self-hosted blogging platform focused on simplicity, structure and authentication.

**Key features**

- Admin dashboard for managing posts, categories and tags
- Secure authentication using password hashing (BCRYPT)
- Session handling and CSRF protection
- SQLite via PDO and server-rendered templates

**Core structure**

- `blog/config.php` — Application configuration and database setup
- `blog/functions.php` — Authentication, CSRF and helper utilities
- `blog/db/schema.php` — Database schema and initialisation
- `blog/index.php`, `blog/post.php` — Public-facing pages
- `blog/admin/` — Content management interface
- `blog/templates/` — View templates
- `blog/assets/` — Styles and static assets
- `blog/setup.php` — Initial setup script (remove or protect after use)

**Running locally**

```bash
cd blog
php -S localhost:8000
```

### calender

A lightweight scheduling and event management system with ICS import/export support.

**Key features**

- Event creation and management
- ICS import and export support
- Token-based API access and admin dashboard

**Core structure**

- `calender/config.php`
- `calender/functions.php`
- `calender/db/schema.php`
- `calender/index.php`, `calender/event.php`
- `calender/admin/` and `calender/templates/`

**Running locally**

```bash
cd calender
php -S localhost:8001
```

### cms

A modular content management system that acts as the central hub.

**Key features**

- Shared user authentication and role-based access
- Module-based architecture and unified session handling
- Extensible integration for other projects

**Core structure**

- `cms/config.php`
- `cms/core/database.php`
- `cms/index.php`, `cms/page.php`
- `cms/admin/` and `cms/templates/`

**Running locally**

```bash
cd cms
php -S localhost:8002
```

### forum

Threaded discussion platform with categories and moderation tools.

**Key features**

- Threaded discussions and category organisation
- Basic moderation and user posting system

**Core structure**

- `forum/config.php`
- `forum/functions.php`
- `forum/index.php`, `forum/thread.php`
- `forum/admin/` and `forum/templates/`

**Running locally**

```bash
cd forum
php -S localhost:8003
```

### store

A minimal e-commerce system with cart and checkout workflow.

**Key features**

- Product listings and product pages
- Shopping cart system and checkout flow
- Payment integration hooks (e.g., PayPal)

**Core structure**

- `store/config.php`
- `store/functions.php`
- `store/index.php`, `store/product.php`
- `store/payments/` and `store/templates/`

**Running locally**

```bash
cd store
php -S localhost:8004
```

## Design Philosophy

- No framework dependency — demonstrates core PHP understanding
- Consistent architecture across systems
- SQLite for portability and simplicity
- Server-side rendering for control and clarity
- Modular design to combine systems under the CMS
- Focus on maintainability and readability

## Security & Setup Notes

- Protect or remove any `setup.php` scripts after initial setup
- Ensure `pdo_sqlite` and common PHP extensions are available
- Do not commit secrets (API keys, payment credentials); use environment variables
- Keep `db/` directories writable by the web user but not publicly exposed