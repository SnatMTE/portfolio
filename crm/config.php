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
// Detect whether the CRM is running inside a CMS installation.
//
// "Inside CMS" means our parent directory IS the CMS root, identifiable by
// the presence of core/auth.php there.  This is true at cms/crm/ but NOT
// when CRM is a sibling of the CMS at portfolio/crm/.
// ---------------------------------------------------------------------------
$_crmParentDir = dirname(CRM_ROOT);

if (file_exists($_crmParentDir . '/core/auth.php')) {
    // -----------------------------------------------------------------------
    // CMS-INTEGRATED MODE: the CRM lives inside the CMS directory.
    // Bootstrap the CMS so we inherit SITE_URL, the session, and auth.
    // -----------------------------------------------------------------------
    if (!defined('CMS_ROOT')) {
        require_once $_crmParentDir . '/config.php';
    }
    if (!function_exists('cmsIsLoggedIn')) {
        require_once CMS_ROOT . '/core/auth.php';
    }
    if (!function_exists('getSetting')) {
        require_once CMS_ROOT . '/core/helpers.php';
    }
    // CRM_URL = CMS base URL + /crm path segment.
    if (!defined('CRM_URL')) {
        define('CRM_URL', rtrim(SITE_URL, '/') . '/crm');
    }
} else {
    // -----------------------------------------------------------------------
    // STANDALONE MODE: no parent CMS.
    // Provide our own SITE_URL, session, and CMS-compatible function stubs.
    // -----------------------------------------------------------------------
    require_once CRM_ROOT . '/core/standalone_boot.php';
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
