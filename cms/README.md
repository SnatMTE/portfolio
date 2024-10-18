# CMS

A modular, portable PHP Content Management System that works in any directory
and automatically integrates with **blog**, **forum**, and **store** modules
placed inside its own folder.

---

## Quick Start

### 1. Deploy the CMS

Place the `cms/` folder anywhere on your web server:

```
/cms/          → https://example.com/cms/
/project/cms/  → https://example.com/project/cms/
/              → https://example.com/
```

### 2. Run Setup

Visit `/cms/setup.php` to:
- Set your site name and tagline
- Create the first admin account

> Delete or protect `setup.php` after first run.

### 3. Log In

Visit `/cms/login.php` (or `/cms/admin/`) and sign in.

---

## Installing Modules

To activate a module, copy its folder **inside** the `cms/` directory:

```
cms/
├── blog/    ← copy blog/ here
├── forum/   ← copy forum/ here
└── store/   ← copy store/ here
```

The CMS auto-detects the module on next page load. No configuration needed.

---

## Module Integration

When a module detects it is inside the CMS (via `../core/database.php`), it:

| Feature            | Behaviour                                              |
|--------------------|--------------------------------------------------------|
| Login page         | Redirects to `/cms/login.php`                         |
| Logout             | Delegates to `/cms/logout.php`                        |
| Session            | Shared — set once by CMS login                        |
| Admin auth         | Redirects to CMS login if unauthenticated             |
| Database           | Module keeps its own SQLite for content tables        |
| User registration  | Forum registration disabled; managed via CMS admin    |

### Session variables set by CMS login

| Variable            | Used by          |
|---------------------|------------------|
| `$_SESSION['user_id']`       | Forum            |
| `$_SESSION['admin_id']`      | Blog, Store      |
| `$_SESSION['username']`      | All              |
| `$_SESSION['admin_username']`| Blog, Store      |
| `$_SESSION['role']`          | Forum `isAdmin()`|

---

## Folder Structure

```
cms/
├── config.php              ← Global config, session, SITE_URL
├── functions.php           ← Convenience loader (config + auth + helpers)
├── index.php               ← Public homepage
├── login.php               ← Unified login (shared by all modules)
├── logout.php              ← Unified logout
├── page.php                ← Static page viewer (?slug=...)
├── setup.php               ← First-run setup wizard
│
├── core/
│   ├── auth.php            ← CMS auth functions (cmsIsLoggedIn, etc.)
│   ├── database.php        ← getCMSDB() PDO singleton (cms.sqlite)
│   ├── helpers.php         ← getSetting, cmsSlugify, cmsFormatDate, etc.
│   └── module_loader.php   ← getActiveModules() scanner
│
├── admin/
│   ├── auth.php            ← Bootstraps every admin page
│   ├── index.php           ← Dashboard
│   ├── users.php           ← User management
│   ├── create_user.php
│   ├── edit_user.php
│   ├── roles.php           ← Role overview
│   ├── pages.php           ← Static page management
│   ├── create_page.php
│   ├── edit_page.php
│   ├── modules.php         ← Installed module overview
│   └── settings.php        ← Site settings
│
├── assets/
│   ├── css/style.css
│   └── js/main.js
│
├── db/
│   ├── schema.php          ← CMS table definitions (users, roles, settings, pages)
│   └── cms.sqlite          ← Created automatically on first run
│
└── templates/
    ├── header.php          ← Public page header
    ├── footer.php          ← Public page footer
    ├── admin_header.php    ← Admin sidebar + page wrapper
    └── admin_footer.php    ← Admin page close
```

---

## CMS Database

`cms.sqlite` contains the CMS core tables. Module content tables remain
in their own SQLite files (`blog.sqlite`, `forum.sqlite`, `store.sqlite`).

| Table      | Purpose                          |
|------------|----------------------------------|
| `roles`    | admin, editor, user              |
| `users`    | Shared user accounts             |
| `settings` | Key/value site configuration     |
| `pages`    | Static content pages             |

---

## Roles

| Role   | Access                              |
|--------|-------------------------------------|
| admin  | Full CMS + all module admin panels  |
| editor | Pages + content (no user mgmt)      |
| user   | Public module features only         |

---

## Security

- PDO prepared statements throughout
- `password_hash()` with bcrypt for passwords
- CSRF tokens on all state-changing forms
- Session regeneration on login
- `httponly` + `samesite=Strict` session cookies
- Role-based access control on all admin routes
- HTML output escaped with `e()` / `htmlspecialchars()`

---

## Module Authors

To add CMS support to a new module, add three things:

**1. `module.php`** — metadata manifest:
```php
return [
    'name'        => 'My Module',
    'description' => 'What it does.',
    'icon'        => '&#128230;',
];
```

**2. CMS detection in `config.php`** (top of file):
```php
if (!defined('CMS_ROOT') && file_exists(__DIR__ . '/../core/database.php')) {
    define('CMS_ROOT', dirname(__DIR__));
}
// After SITE_URL is defined:
if (defined('CMS_ROOT') && !defined('CMS_URL')) {
    $__parts = explode('/', rtrim(SITE_URL, '/'));
    array_pop($__parts);
    define('CMS_URL', implode('/', $__parts));
    unset($__parts);
}
```

**3. CMS login redirect in `login.php`**:
```php
if (defined('CMS_ROOT')) {
    header('Location: ' . (defined('CMS_URL') ? CMS_URL . '/login.php' : '../login.php'));
    exit;
}
```
