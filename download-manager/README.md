# Download Manager

A lightweight, secure file hosting and distribution module built with PHP and SQLite.

**Author:** M. Terra Ellis  
**Link:** https://terra.me.uk

---

## Features

- Public file listing with search, category filtering, and pagination
- Secure download handler — files are never directly URL-accessible
- Admin panel with upload, edit, and delete management
- Private files with token-based secure access links
- Drag-and-drop upload with MIME validation and size limits
- Download count tracking per file
- Demo mode via `?demo=1` or a `DEMO` file
- Standalone or CMS-integrated operation

---

## Quick Start (Standalone)

**Requirements:** PHP 7.4+, `pdo_sqlite`, `fileinfo` extensions.

```bash
cd download-manager
php -S localhost:8000
```

1. Open `http://localhost:8000/setup.php` in your browser.
2. Create the initial admin account.
3. **Delete `setup.php` immediately after setup.**
4. Log in at `/login.php` and start uploading files.

---

## Folder Structure

```
download-manager/
├── index.php              # Public file listing
├── download.php           # Secure download handler
├── upload.php             # Upload a file (admin only)
├── edit.php               # Edit file details (admin only)
├── delete.php             # Delete a file (admin only, POST)
├── login.php              # Standalone admin login
├── logout.php             # Session logout
├── config.php             # Configuration and DB connection
├── functions.php          # Core helper functions
├── setup.php              # First-run admin account setup
├── module.php             # CMS module manifest
│
├── admin/
│   ├── auth.php           # Authentication helpers
│   ├── index.php          # Admin dashboard
│   └── files.php          # File management table
│
├── core/
│   ├── upload_handler.php # Secure upload processing
│   ├── download_handler.php # File streaming
│   └── file_helper.php    # Filesystem utilities
│
├── assets/
│   ├── css/style.css      # Stylesheet (White + Orange palette)
│   └── js/main.js         # Navigation toggle, drag-drop
│
├── templates/
│   ├── header.php         # Public site header
│   ├── footer.php         # Public site footer
│   ├── admin_nav.php      # Admin sidebar navigation
│   ├── file_item.php      # File card component
│   └── upload_form.php    # Shared upload/edit form
│
├── db/
│   ├── schema.php         # Table definitions (dm_downloads, dm_download_tokens)
│   └── demo_seed.php      # Demo data seeder
│
└── storage/               # Uploaded files (not web-accessible)
```

---

## Database Tables

### `dm_downloads`

| Column         | Type    | Notes                              |
|----------------|---------|------------------------------------|
| id             | INTEGER | Primary key                        |
| user_id        | INTEGER | Uploader (nullable)                |
| title          | TEXT    | Display name                       |
| description    | TEXT    | Short description                  |
| file_path      | TEXT    | Stored filename in `storage/`      |
| original_name  | TEXT    | Original filename shown to users   |
| file_size      | INTEGER | Bytes                              |
| mime_type      | TEXT    | Detected MIME type                 |
| category       | TEXT    | Optional category label            |
| download_count | INTEGER | Incremented on each download       |
| visibility     | TEXT    | `public` or `private`              |
| created_at     | TEXT    | SQLite datetime                    |
| updated_at     | TEXT    | SQLite datetime                    |

### `dm_download_tokens`

| Column      | Type    | Notes                          |
|-------------|---------|--------------------------------|
| id          | INTEGER | Primary key                    |
| download_id | INTEGER | FK → dm_downloads(id)          |
| token       | TEXT    | 64-character hex token (unique)|
| expires_at  | TEXT    | Token expiry datetime          |
| created_at  | TEXT    | SQLite datetime                |

---

## Secure Downloads

Files in `storage/` are not served directly. All downloads go through:

```
/download.php?id=123
```

For private files, a time-limited token is required:

```
/download.php?id=123&token=<64-hex-token>
```

Generate a token programmatically:

```php
require_once 'functions.php';
$token = createDownloadToken($downloadId, expiresInHours: 48);
echo SITE_URL . '/download.php?id=' . $downloadId . '&token=' . $token;
```

---

## CMS Integration

Place this directory at `/cms/downloads/`. The module automatically detects the CMS via the presence of `../core/database.php` and:

- Shares the CMS SQLite database (`cms.sqlite`) for all download tables
- Defers authentication to the CMS session (`$_SESSION['admin_id']`)
- Registers itself via `module.php` for the CMS admin sidebar

```php
// /cms/downloads/module.php is auto-detected by cms/core/module_loader.php
```

No separate login or database is needed in CMS mode.

---

## Security Notes

- Files are stored in `storage/` with randomised filenames — direct guessing is not practical.
- MIME type is validated server-side with `finfo`, not trusted from the browser.
- All database queries use PDO prepared statements.
- CSRF tokens protect every mutating form and POST action.
- `setup.php` blocks after first use and should be deleted from the server.
- The `storage/` directory contains an `index.html` blocker; add an `.htaccess` `Deny from all` rule in production with Apache.

---

## Configuration

Key constants defined in `config.php`:

| Constant          | Default          | Purpose                          |
|-------------------|------------------|----------------------------------|
| `DM_STORAGE`      | `./storage`      | Absolute path to file storage    |
| `DM_MAX_UPLOAD`   | 20 971 520 (20 MB) | Maximum upload size in bytes   |
| `DM_ALLOWED_TYPES`| See config.php   | Permitted MIME types             |
| `DM_DB_FILE`      | `db/downloads.sqlite` | SQLite file (standalone only) |
| `SITE_NAME`       | `Download Manager`| Site name in header/title       |
| `SITE_TAGLINE`    | See config.php   | Tagline under site name          |
