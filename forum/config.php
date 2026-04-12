<?php
/**
 * config.php
 *
 * Central configuration for the Forum.
 * Defines the database path, site constants, and initialises the PDO connection.
 *
 * CMS Integration
 * ---------------
 * When placed inside a CMS (detected by ../core/database.php), defines CMS_ROOT
 * and CMS_URL so that login/logout redirect to the shared CMS auth pages.
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

define('ROOT_PATH', __DIR__);
define('DB_FILE',   ROOT_PATH . '/db/forum.sqlite');

// ---------------------------------------------------------------------------
// Site URL auto-detection
// ---------------------------------------------------------------------------
if (!defined('SITE_URL')) {
    /**
     * Detects the forum's base URL from server variables.
     *
     * @return string  Base URL with no trailing slash, e.g. "https://example.com/forum".
     */
    function detectSiteUrl(): string
    {
        $proto = 'http';
        if (
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['REQUEST_SCHEME'])         && $_SERVER['REQUEST_SCHEME'] === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL'])   && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
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
        }

        if ($basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        return $proto . '://' . $host . $basePath;
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

// ---------------------------------------------------------------------------
// Forum constants
// ---------------------------------------------------------------------------
define('FORUM_NAME',       'Forum');
define('FORUM_TAGLINE',    'Community discussions');
define('THREADS_PER_PAGE', 20);
define('POSTS_PER_PAGE',   20);

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------------
// Database (PDO singleton)
// ---------------------------------------------------------------------------

/**
 * Returns the shared PDO instance, creating it on first call.
 * The schema is initialised automatically on the first connection.
 *
 * @return PDO
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $isDemo = (isset($_GET['demo']) && $_GET['demo'] === '1') || file_exists(ROOT_PATH . '/DEMO');

        $dbDir = ROOT_PATH . '/db';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed.');
        }

        require_once ROOT_PATH . '/db/schema.php';

        if ($isDemo && file_exists(ROOT_PATH . '/db/demo_seed.php')) {
            require_once ROOT_PATH . '/db/demo_seed.php';
            if (function_exists('seedDemoForum')) {
                seedDemoForum($pdo);
            }
        }
    }

    return $pdo;
}
