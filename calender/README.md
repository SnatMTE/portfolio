# Calendar Module

A lightweight PHP calendar module for managing events, importing/exporting iCalendar (.ics) files and providing live subscription feeds for Google Calendar, Apple Calendar, and Outlook.

## Features

- **Monthly calendar grid** with event chips, today highlight, and quick-add
- **Event management** – create, edit, delete events with title, description, times, location, and public/private visibility
- **Import .ics** – upload any valid iCalendar file and parse events into the database
- **Export .ics** – download all or filtered events as a standards-compliant `.ics` file
- **Calendar sync URL** – live feed at `/sync.php` (public) or `/sync.php?token=TOKEN` (private), compatible with all major calendar apps
- **Token management** – generate and revoke private sync tokens from the admin panel
- **CMS integration** – when placed inside `/cms/calendar/` it shares the CMS database and session; no separate login or database file is created
- **Standalone mode** – run independently with its own SQLite database and admin login

## Requirements

- PHP 7.4+ (PHP 8.x recommended)
- PDO with SQLite driver (`pdo_sqlite`)
- Writable `db/` directory (standalone) or writable CMS `db/` (integrated)

## Quick Start – Standalone

```bash
cd calender
php -S localhost:8000
```

1. Open `http://localhost:8000/setup.php` in your browser.
2. Create your admin account.
3. **Delete `setup.php` immediately after setup.**
4. Navigate to `http://localhost:8000` to see the calendar.
5. Sign in at `/login.php` to manage events.

## Quick Start – CMS Integration

1. Copy (or symlink) this folder to `cms/calendar/`.
2. The module auto-detects the CMS and shares its database and session.
3. Log in to the CMS admin panel – the Calendar module will appear automatically.
4. Access the calendar at `http://yoursite/cms/calendar/`.

## Folder Structure

```
calender/
├── index.php               # Monthly calendar view
├── event.php               # Event detail page
├── create_event.php        # Create event form
├── edit_event.php          # Edit event form
├── import.php              # Import .ics file
├── export.php              # Export .ics (with optional filters)
├── sync.php                # Live calendar feed
├── config.php              # Configuration & DB connection
├── functions.php           # Helper functions
├── login.php               # Standalone admin login
├── logout.php              # Session destroy
├── setup.php               # First-run admin creation (DELETE AFTER USE)
├── module.php              # CMS module manifest
├── DEMO                    # Demo mode trigger (remove in production)
│
├── admin/
│   ├── auth.php            # Auth guard
│   ├── index.php           # Admin dashboard
│   ├── events.php          # Manage all events
│   └── tokens.php          # Manage sync tokens
│
├── core/
│   ├── ics_parser.php      # iCalendar import parser
│   ├── ics_generator.php   # iCalendar export generator
│   └── calendar_helper.php # Grid builder & date utilities
│
├── assets/
│   ├── css/style.css       # Full standalone stylesheet
│   ├── js/main.js          # Calendar interactions
│   └── images/
│
├── templates/
│   ├── header.php          # Shared HTML header & nav
│   ├── footer.php          # Shared HTML footer
│   ├── admin_nav.php       # Admin sidebar
│   ├── calendar_grid.php   # Monthly grid partial
│   ├── event_modal.php     # Quick-preview modal
│   └── event_item.php      # Event list item
│
└── db/
    ├── schema.php          # Table definitions
    └── demo_seed.php       # Demo data (10 sample events)
```

## Calendar Sync URLs

| URL | Access |
|-----|--------|
| `/sync.php` | Public events only, no auth required |
| `/sync.php?token=TOKEN` | All events for that token's owner |

### Subscribe in Google Calendar

1. In Google Calendar, click **+ Other calendars → From URL**.
2. Paste: `http://yoursite/calender/sync.php`
3. Click **Add Calendar**.

### Subscribe in Apple Calendar

1. In Calendar.app, choose **File → New Calendar Subscription**.
2. Paste the sync URL and click **Subscribe**.

### Subscribe in Outlook

1. In Outlook, choose **Add calendar → From Internet**.
2. Paste the sync URL and click **OK**.

## Security Notes

- Delete `setup.php` after initial admin creation.
- Remove the `DEMO` file before going to production.
- The `db/` directory must not be publicly accessible. Add a `.htaccess` rule:

```apache
<Files "*.sqlite">
    Require all denied
</Files>
```
- Sync tokens use 48-character cryptographically random hex strings.
- All database queries use PDO prepared statements.
- All output is escaped with `htmlspecialchars()`.

## Author

Snat · [https://terra.me.uk](https://terra.me.uk)
