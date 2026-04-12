<?php
/**
 * calendar/config.php
 *
 * Central configuration for the Calendar module.
 * Works standalone (calendar/) or integrated inside /cms/calendar/.
 *
 * CMS Integration
 * ---------------
 * When placed inside a CMS (detected via ../core/database.php), the calendar
 * shares the CMS session and SQLite database. Calendar tables are added to
 * the shared CMS database; no separate DB file is created.
 *
 * @author  M. Terra Ellis
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

/** Absolute filesystem path to this module's root (no trailing slash). */
define('ROOT_PATH', __DIR__);

/** Absolute path to the standalone SQLite database file. */
define('DB_FILE', ROOT_PATH . '/db/calendar.sqlite');

/** Cost factor for password_hash(). */
define('HASH_COST', 12);

/** Events shown per page in list views. */
define('EVENTS_PER_PAGE', 20);

// ---------------------------------------------------------------------------
// Site URL auto-detection
// ---------------------------------------------------------------------------
if (!defined('SITE_URL')) {
    /**
     * Detects the calendar module's base URL from server variables.
     * Handles subdirectory deployments, custom ports, and reverse proxies.
     *
     * @return string  Base URL with no trailing slash.
     */
    function detectCalSiteUrl(): string
    {
        $proto = 'http';
        if (
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['REQUEST_SCHEME'])         && $_SERVER['REQUEST_SCHEME'] === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL'])   && strtolower($_SERVER['HTTP_X_FORWARDED_SSL'])   === 'on')
            || (isset($_SERVER['SERVER_PORT'])             && (int) $_SERVER['SERVER_PORT'] === 443)
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

    define('SITE_URL', detectCalSiteUrl());
}

// When inside CMS, compute CMS_URL as the parent of SITE_URL.
if (defined('CMS_ROOT') && !defined('CMS_URL')) {
    $__parts = explode('/', rtrim(SITE_URL, '/'));
    array_pop($__parts);
    define('CMS_URL', implode('/', $__parts));
    unset($__parts);
}

/** Human-readable module name. */
define('SITE_NAME',    'Calendar');

/** Short tagline. */
define('SITE_TAGLINE', 'Your events, organised');

// ---------------------------------------------------------------------------
// Session (skip if CMS already started one)
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
// Database connection
// ---------------------------------------------------------------------------

/**
 * Returns a singleton PDO instance for the calendar module.
 *
 * CMS mode  : returns the shared CMS database (getCMSDB()) and ensures
 *             calendar tables exist within it.
 * Standalone: connects to calendar/db/calendar.sqlite and initialises
 *             schema + demo seed as needed.
 *
 * @return PDO
 * @throws RuntimeException on connection failure.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // -----------------------------------------------------------
    // CMS mode: share the CMS database
    // -----------------------------------------------------------
    if (defined('CMS_ROOT') && file_exists(CMS_ROOT . '/core/database.php')) {
        if (!function_exists('getCMSDB')) {
            require_once CMS_ROOT . '/core/database.php';
        }
        $pdo = getCMSDB();
        // Ensure calendar-specific tables exist in the shared DB.
        require_once ROOT_PATH . '/db/schema.php';
        initCalendarSchema($pdo);
        return $pdo;
    }

    // -----------------------------------------------------------
    // Standalone mode: own SQLite file
    // -----------------------------------------------------------
    $dbDir = dirname(DB_FILE);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0750, true);
    }

    try {
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Database connection failed.');
    }

    require_once ROOT_PATH . '/db/schema.php';
    initCalendarSchema($pdo);

    // Seed demo data when requested
    $isDemo = (isset($_GET['demo']) && $_GET['demo'] === '1') || file_exists(ROOT_PATH . '/DEMO');
    if ($isDemo && file_exists(ROOT_PATH . '/db/demo_seed.php')) {
        require_once ROOT_PATH . '/db/demo_seed.php';
        if (function_exists('seedDemoCalendar')) {
            seedDemoCalendar($pdo);
        }
    }

    return $pdo;
}
