<?php
/**
 * config.php
 *
 * Central configuration for the Download Manager module.
 * Handles both standalone and CMS-integrated operation modes.
 *
 * CMS Integration
 * ---------------
 * When placed inside /cms/downloads/, the presence of ../core/database.php
 * triggers CMS mode. In that case the module shares the CMS SQLite database
 * (via getCMSDB()) and defers authentication to the CMS session.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

// ---------------------------------------------------------------------------
// CMS detection — must run before anything else defines constants
// ---------------------------------------------------------------------------
$_dmCmsDb = __DIR__ . '/../core/database.php';
if (!defined('CMS_ROOT') && file_exists($_dmCmsDb)) {
    // Pull in CMS config: defines CMS_ROOT, CMS_DB_FILE, getCMSDB(), session, etc.
    require_once __DIR__ . '/../config.php';
    /** Flag indicating that this module is running inside the CMS. */
    define('DM_CMS_MODE', true);
}
unset($_dmCmsDb);

// ---------------------------------------------------------------------------
// Module root
// ---------------------------------------------------------------------------

/** Absolute filesystem path to this module's directory (no trailing slash). */
define('DM_ROOT', __DIR__);

/** Directory where uploaded files are physically stored. Not web-accessible. */
define('DM_STORAGE', DM_ROOT . '/storage');

/** Maximum allowed upload size in bytes (20 MB default). */
define('DM_MAX_UPLOAD', 20 * 1024 * 1024);

/**
 * Allowed MIME types for file uploads.
 *
 * Extend this list as needed. MIME is validated server-side with finfo.
 */
define('DM_ALLOWED_TYPES', [
    'application/pdf',
    'application/zip',
    'application/x-zip-compressed',
    'application/x-tar',
    'application/gzip',
    'application/x-gzip',
    'text/plain',
    'text/csv',
    'image/png',
    'image/jpeg',
    'image/gif',
    'image/webp',
    'image/svg+xml',
    'audio/mpeg',
    'audio/ogg',
    'video/mp4',
    'application/octet-stream',
]);

/** Standalone SQLite database file. Not used in CMS mode. */
define('DM_DB_FILE', DM_ROOT . '/db/downloads.sqlite');

/** bcrypt cost factor — only defined here if the CMS has not already set it. */
if (!defined('HASH_COST')) {
    define('HASH_COST', 12);
}

// ---------------------------------------------------------------------------
// Site constants — standalone only (CMS provides its own)
// ---------------------------------------------------------------------------
if (!defined('CMS_ROOT')) {
    /** Alias so helper functions can use ROOT_PATH interchangeably. */
    define('ROOT_PATH', DM_ROOT);

    if (!defined('SITE_URL')) {
        /**
         * Detects the module's base URL from server variables.
         *
         * Mirrors the same heuristic used in the blog and other modules.
         *
         * @return string  Base URL with no trailing slash.
         */
        function detectDmSiteUrl(): string
        {
            $proto = 'http';
            if (
                (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['REQUEST_SCHEME'])          && $_SERVER['REQUEST_SCHEME'] === 'https')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])  && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
                || (isset($_SERVER['HTTP_X_FORWARDED_SSL'])    && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
                || (isset($_SERVER['SERVER_PORT'])              && (int) $_SERVER['SERVER_PORT'] === 443)
            ) {
                $proto = 'https';
            }

            $host = $_SERVER['HTTP_HOST']
                ?? $_SERVER['SERVER_NAME']
                ?? ($_SERVER['SERVER_ADDR'] ?? 'localhost');

            if (strpos($host, ':') === false && isset($_SERVER['SERVER_PORT'])) {
                $port = (int) $_SERVER['SERVER_PORT'];
                if ($port > 0 && !in_array($port, [80, 443], true)) {
                    $host .= ':' . $port;
                }
            }

            $basePath = '';
            $docRoot  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
            $rootPath = realpath(DM_ROOT);

            if ($docRoot && $rootPath && strpos($rootPath, $docRoot) === 0) {
                $basePath = str_replace('\\', '/', substr($rootPath, strlen($docRoot)));
                $basePath = rtrim($basePath, '/');
            }

            // Fallback to script dirname when document root detection fails
            if ($basePath === '') {
                $script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/');
                $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/');
                if ($basePath === '/' || $basePath === '.') {
                    $basePath = '';
                }
            }

            return rtrim($proto . '://' . $host . $basePath, '/');
        }

        define('SITE_URL', detectDmSiteUrl());
    }

    /** Human-readable site name shown in the browser title and header. */
    define('SITE_NAME', 'Download Manager');

    /** Short tagline displayed beneath the site name in the header. */
    define('SITE_TAGLINE', 'Secure file hosting & distribution');
}

// ---------------------------------------------------------------------------
// Start session (only when not already active)
// ---------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// Database connection
// ---------------------------------------------------------------------------

/**
 * Returns a singleton PDO connection to the downloads database.
 *
 * In CMS mode the CMS SQLite database is reused (via getCMSDB()) so all
 * download tables live alongside CMS content in a single file.
 * In standalone mode a dedicated downloads.sqlite file is used.
 *
 * @return PDO  Active PDO connection with FK enforcement and WAL journal.
 *
 * @throws PDOException  If the database file cannot be opened or created.
 */
function getDB(): PDO
{
    // Share the CMS database when running inside the CMS
    if (defined('DM_CMS_MODE') && DM_CMS_MODE) {
        return getCMSDB();
    }

    static $pdo = null;

    if ($pdo === null) {
        $dbDir = dirname(DM_DB_FILE);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0750, true);
        }

        try {
            $pdo = new PDO('sqlite:' . DM_DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            $pdo->exec('PRAGMA journal_mode = WAL;');
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed.');
        }
    }

    return $pdo;
}

// ---------------------------------------------------------------------------
// Ensure the file storage directory exists on every request
// ---------------------------------------------------------------------------
if (!is_dir(DM_STORAGE)) {
    mkdir(DM_STORAGE, 0750, true);
}

// Also drop a placeholder so the directory is never web-browsable
$_dmStorageIndex = DM_STORAGE . '/index.html';
if (!file_exists($_dmStorageIndex)) {
    file_put_contents($_dmStorageIndex, '<!DOCTYPE html><html><body>Access denied.</body></html>');
}
unset($_dmStorageIndex);

// ---------------------------------------------------------------------------
// Bootstrap schema and optional demo data
// ---------------------------------------------------------------------------
require_once DM_ROOT . '/db/schema.php';

if ((isset($_GET['demo']) && $_GET['demo'] === '1') || file_exists(DM_ROOT . '/DEMO')) {
    if (file_exists(DM_ROOT . '/db/demo_seed.php')) {
        require_once DM_ROOT . '/db/demo_seed.php';
        if (function_exists('seedDemoDownloads')) {
            seedDemoDownloads(getDB());
        }
    }
}
