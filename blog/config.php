<?php
/**
 * config.php
 *
 * Central configuration file for the portfolio blog.
 * Defines database path, site constants, and initialises the PDO connection.
 *
 * CMS Integration
 * ---------------
 * When this module is placed inside a CMS (detected by the presence of
 * ../core/database.php), it defines CMS_ROOT and computes CMS_URL so that
 * login/logout redirects point to the shared CMS authentication pages.
 * The session is shared; the module's own getDB() still connects to the
 * module's own SQLite file for content tables.
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
define('DB_FILE', ROOT_PATH . '/db/blog.sqlite');

/**
 * Public-facing base URL (no trailing slash).
 *
 * Auto-detected at runtime so the site works when deployed to a
 * subdirectory or different host without needing a hard-coded value.
 */
if (!defined('SITE_URL')) {
    /**
     * Detect the application's base URL from server variables.
     *
     * Heuristics used:
     *  - Respect `X-Forwarded-*` headers where present.
     *  - Prefer `HTTP_HOST`, fallback to `SERVER_NAME` or `SERVER_ADDR`.
     *  - Determine the base path by comparing `DOCUMENT_ROOT` to `ROOT_PATH`.
     *  - Fallback to `dirname($_SERVER['SCRIPT_NAME'])` when necessary.
     *
     * Returns a string like "https://example.com" or
     * "https://example.com/blog" (no trailing slash).
     *
     * @return string
     */
    function detectSiteUrl(): string
    {
        // Determine protocol
        $proto = 'http';
        if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '')
            || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        ) {
            $proto = 'https';
        }

        // Hostname (may include port)
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ($_SERVER['SERVER_ADDR'] ?? 'localhost');

        // If HTTP_HOST did not include a port and the server port is non-standard,
        // append it so the returned URL is correct for custom ports.
        if (strpos($host, ':') === false && isset($_SERVER['SERVER_PORT'])) {
            $port = (int) $_SERVER['SERVER_PORT'];
            if ($port > 0 && !in_array($port, [80, 443], true)) {
                $host .= ':' . $port;
            }
        }

        // Compute base path by comparing document root to the project root.
        $basePath = '';
        $docRoot  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        $rootPath = realpath(ROOT_PATH);
        if ($docRoot && $rootPath && strpos($rootPath, $docRoot) === 0) {
            $basePath = str_replace('\\', '/', substr($rootPath, strlen($docRoot)));
            $basePath = rtrim($basePath, '/');
        }

        // Fallback: use the script dirname when document root detection fails
        if ($basePath === '') {
            $script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/');
            $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/');
            if ($basePath === '/' || $basePath === '.') {
                $basePath = '';
            }
        }

        $url = $proto . '://' . $host . ($basePath !== '' ? $basePath : '');
        return rtrim($url, '/');
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
define('SITE_NAME', 'Snat\'s Blog');

/** Short tagline displayed in the site header beneath the title. */
define('SITE_TAGLINE', 'Projects, thoughts & tutorials');

/** Default author name used when no other author is specified. */
define('DEFAULT_AUTHOR', 'Snat');

/** Number of posts displayed per page on the blog listing. */
define('POSTS_PER_PAGE', 10);

/** Salt rounds / cost factor for password_hash(). */
define('HASH_COST', 12);

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
// Database connection
// ---------------------------------------------------------------------------

/**
 * Returns a singleton PDO instance connected to the SQLite database.
 *
 * Uses SQLite via PDO with error-mode set to EXCEPTION so every query
 * failure throws a catchable PDOException rather than silently failing.
 * Foreign key support is enabled on every new connection.
 *
 * @return PDO  The active PDO database connection.
 *
 * @throws PDOException If the database file cannot be opened or created.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Ensure the db/ directory exists before SQLite tries to create the file
        $dbDir = dirname(DB_FILE);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0750, true);
        }

        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);

        // Enable FK enforcement for this connection
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    return $pdo;
}

// ---------------------------------------------------------------------------
// Initialise schema on first run
// ---------------------------------------------------------------------------
require_once ROOT_PATH . '/db/schema.php';
