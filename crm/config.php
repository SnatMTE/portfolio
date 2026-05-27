<?php
/**
 * crm/config.php
 *
 * Bootstraps the CRM module.
 * Defines CRM_ROOT, CRM_DB_FILE, and loads the shared CMS config so the
 * CRM inherits the session, SITE_URL, and all CMS helper functions.
 *
 * Load order:
 *   1. This file is included by any CRM page.
 *   2. It pulls in the parent CMS config (authentication, DB helpers, etc.).
 *   3. It then provides the CRM-specific database connection via getCRMDB().
 */

/** Absolute path to the CRM directory (no trailing slash). */
define('CRM_ROOT', __DIR__);

/** CRM module version. */
define('CRM_VERSION', '1.0.0');

/** CRM display name used in page titles and the sidebar. */
define('CRM_NAME', 'CRM');

// ---------------------------------------------------------------------------
// Bootstrap the parent CMS so we inherit SITE_URL, session handling,
// CSRF helpers, and the CMS user/auth functions.
// ---------------------------------------------------------------------------
$_cmsConfig = dirname(__DIR__) . '/cms/config.php';
if (!defined('CMS_ROOT') && file_exists($_cmsConfig)) {
    require_once $_cmsConfig;
} elseif (!defined('CMS_ROOT')) {
    // Running standalone — provide a minimal stub so the CRM doesn't explode
    // if the CMS hasn't been deployed yet.
    http_response_code(503);
    exit('CRM requires the CMS module. Ensure cms/config.php is present.');
}

// Load CMS auth and helpers if not already loaded (config.php pulls them in,
// but guard against double-include just in case).
if (!function_exists('cmsIsLoggedIn')) {
    require_once CMS_ROOT . '/core/auth.php';
}
if (!function_exists('getSetting')) {
    require_once CMS_ROOT . '/core/helpers.php';
}

// ---------------------------------------------------------------------------
// CRM database path
// ---------------------------------------------------------------------------

/**
 * Absolute path to the CRM SQLite database file.
 * Stored inside the CRM module's own db/ directory.
 * Move this outside the webroot for production hardening.
 */
define('CRM_DB_FILE', CRM_ROOT . '/db/crm.sqlite');

// ---------------------------------------------------------------------------
// CRM database connection
// ---------------------------------------------------------------------------

require_once CRM_ROOT . '/core/database.php';
require_once CRM_ROOT . '/core/helpers.php';
