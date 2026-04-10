<?php
/**
 * config.php
 *
 * Central configuration file for the portfolio online store.
 * Defines database path, site constants, PayPal credentials,
 * and initialises the PDO connection.
 *
 * CMS Integration
 * ---------------
 * When placed inside a CMS (detected by ../core/database.php), defines CMS_ROOT
 * and CMS_URL so that login/logout redirect to the shared CMS auth pages.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

// ---------------------------------------------------------------------------
// CMS detection
// ---------------------------------------------------------------------------
if (!defined('CMS_ROOT') && file_exists(__DIR__ . '/../core/database.php')) {
    define('CMS_ROOT', dirname(__DIR__));
}

// ---------------------------------------------------------------------------
// Site constants
// ---------------------------------------------------------------------------

/** Absolute filesystem path to the project root (no trailing slash). */
define('ROOT_PATH', __DIR__);

/** Absolute path to the SQLite database file. */
define('DB_FILE', ROOT_PATH . '/db/store.sqlite');

/**
 * Public-facing base URL (no trailing slash).
 *
 * Auto-detected at runtime so the site works when deployed to a
 * subdirectory or different host without needing a hard-coded value.
 */
if (!defined('SITE_URL')) {
    
    /**
     * detectSiteUrl — Short description of the function's behaviour.
     *
     * @return string
     */
    function detectSiteUrl(): string
    {
        $proto = 'http';
        if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '')
            || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        ) {
            $proto = 'https';
        }

        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ($_SERVER['SERVER_ADDR'] ?? 'localhost');

        if (strpos($host, ':') === false && isset($_SERVER['SERVER_PORT'])) {
            $port = (int) $_SERVER['SERVER_PORT'];
            if ($port > 0 && !in_array($port, [80, 443], true)) {
                $host .= ':' . $port;
            }
        }

        $basePath = '';
        $docRoot  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        $rootPath = realpath(ROOT_PATH);
        if ($docRoot && $rootPath && strpos($rootPath, $docRoot) === 0) {
            $basePath = str_replace('\\', '/', substr($rootPath, strlen($docRoot)));
            $basePath = rtrim($basePath, '/');
        }

        if ($basePath === '') {
            $script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/');
            $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/');
            if ($basePath === '/' || $basePath === '.') {
                $basePath = '';
            }
        }

        return rtrim($proto . '://' . $host . $basePath, '/');
    }

    define('SITE_URL', detectSiteUrl());
}

// When inside CMS, compute CMS_URL as the parent of this module's SITE_URL.
if (defined('CMS_ROOT') && !defined('CMS_URL')) {
    $__parts = explode('/', rtrim(SITE_URL, '/'));
    array_pop($__parts);
    define('CMS_URL', implode('/', $__parts));
    unset($__parts);
}

/** Human-readable site name shown in the browser title and header. */
define('SITE_NAME', "Snat's Store");

/** Short tagline displayed in the site header beneath the title. */
define('SITE_TAGLINE', 'Quality products, simple checkout');

/** Number of products displayed per page on the listing page. */
define('PRODUCTS_PER_PAGE', 12);

/** Salt rounds / cost factor for password_hash(). */
define('HASH_COST', 12);

// ---------------------------------------------------------------------------
// PayPal API credentials
// ---------------------------------------------------------------------------

/**
 * PayPal mode: 'sandbox' for testing, 'live' for production.
 * Switch to 'live' when deploying and replace the credentials below.
 */
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' | 'live'

/** PayPal REST API client ID (from developer.paypal.com). */
define('PAYPAL_CLIENT_ID', 'YOUR_SANDBOX_CLIENT_ID');

/** PayPal REST API secret (from developer.paypal.com). */
define('PAYPAL_SECRET', 'YOUR_SANDBOX_SECRET');

/**
 * PayPal API base URL, selected automatically based on PAYPAL_MODE.
 * Sandbox: https://api-m.sandbox.paypal.com
 * Live:    https://api-m.paypal.com
 */
define(
    'PAYPAL_API_BASE',
    PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com'
);

/** Currency code used for all transactions. */
define('CURRENCY', 'GBP');

// ---------------------------------------------------------------------------
// Currency symbol helper (not a constant – computed once)
// ---------------------------------------------------------------------------
define('CURRENCY_SYMBOL', match (CURRENCY) {
    'GBP' => '£',
    'USD' => '$',
    'EUR' => '€',
    default => CURRENCY . ' ',
});

// ---------------------------------------------------------------------------
// Start session (only if one is not already active)
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// PDO connection (singleton)
// ---------------------------------------------------------------------------

/**
 * Returns the single PDO instance for the store database.
 *
 * Creates the database directory and file on first use. Enables WAL journal
 * mode, foreign key enforcement, and busy timeout for concurrent access.
 *
 * @return PDO
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dir = dirname(DB_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec("PRAGMA journal_mode = WAL");
    $pdo->exec("PRAGMA foreign_keys = ON");
    $pdo->exec("PRAGMA busy_timeout = 5000");

    return $pdo;
}

// ---------------------------------------------------------------------------
// Boot: initialise schema
// ---------------------------------------------------------------------------
require_once ROOT_PATH . '/db/schema.php';
