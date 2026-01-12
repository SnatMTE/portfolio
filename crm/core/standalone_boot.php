<?php
/**
 * crm/core/standalone_boot.php
 *
 * Bootstraps the CRM module for standalone operation — i.e. when it is NOT
 * nested inside a CMS installation.
 *
 * Responsibilities:
 *   - Starts the PHP session.
 *   - Auto-detects SITE_URL (server root) and CRM_URL (this module's root).
 *   - Defines HASH_COST.
 *   - Loads standalone_auth.php which provides CMS-compatible function stubs.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('HASH_COST')) {
    define('HASH_COST', 12);
}

/** True when the CRM is running without a parent CMS installation. */
define('CRM_STANDALONE', true);

// ---------------------------------------------------------------------------
// SITE_URL / CRM_URL detection
//
// SITE_URL = scheme + host (the server root, e.g. http://localhost:3000).
//   This matches the pattern used throughout CRM pages:
//   CRM_URL . '/customers/' => http://localhost:3000/crm/customers/
//
// CRM_URL  = SITE_URL + path to the crm/ directory (no trailing slash).
//   Used everywhere a path inside the CRM module is needed.
// ---------------------------------------------------------------------------
if (!defined('SITE_URL') || !defined('CRM_URL')) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $crmDir  = realpath(CRM_ROOT);

    if ($docRoot !== false && $crmDir !== false && str_starts_with($crmDir, $docRoot)) {
        // Build the CRM path relative to document root, e.g. /crm
        $relPath = str_replace('\\', '/', substr($crmDir, strlen($docRoot)));
    } else {
        // Can't resolve — assume /crm.  The admin can override via constants
        // defined before including config.php if necessary.
        $relPath = '/crm';
    }

    $siteRoot = $scheme . '://' . $host;        // e.g. http://localhost:3000
    $crmUrl   = $siteRoot . $relPath;           // e.g. http://localhost:3000/crm

    if (!defined('SITE_URL')) {
        define('SITE_URL', $siteRoot);
    }
    if (!defined('CRM_URL')) {
        define('CRM_URL', rtrim($crmUrl, '/'));
    }
}

require_once __DIR__ . '/standalone_auth.php';
