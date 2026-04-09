<?php
/**
 * cms/config.php
 *
 * Central CMS configuration.
 * Defines CMS_ROOT, CMS_DB_FILE, site constants, and starts the session.
 * Loads the core database provider (getCMSDB) and module loader.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

// ---------------------------------------------------------------------------
// CMS root & database path
// ---------------------------------------------------------------------------

/** Absolute path to the CMS directory (no trailing slash). */
define('CMS_ROOT', __DIR__);

/** Absolute path to the CMS SQLite database. */
define('CMS_DB_FILE', CMS_ROOT . '/db/cms.sqlite');

/** Cost factor for password_hash(). */
define('HASH_COST', 12);

// ---------------------------------------------------------------------------
// Site URL auto-detection
// ---------------------------------------------------------------------------
if (!defined('SITE_URL')) {
    /**
     * Detects the CMS base URL from server variables.
     *
     * @return string  Base URL with no trailing slash.
     */
    function detectCmsSiteUrl(): string
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
        $rootPath = realpath(CMS_ROOT);

        if ($docRoot && $rootPath && strpos($rootPath, $docRoot) === 0) {
            $basePath = str_replace('\\', '/', substr($rootPath, strlen($docRoot)));
            $basePath = rtrim($basePath, '/');
        }

        if ($basePath === '') {
            $script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/');
            $basePath = rtrim(str_replace('\\', '/', dirname($script)), '/');
            if ($basePath === '.' || $basePath === '/') {
                $basePath = '';
            }
        }

        return $proto . '://' . $host . $basePath;
    }

    define('SITE_URL', detectCmsSiteUrl());
}

/** Also expose as CMS_URL for use by modules checking parent CMS. */
if (!defined('CMS_URL')) {
    define('CMS_URL', SITE_URL);
}

// ---------------------------------------------------------------------------
// CMS display constants
// ---------------------------------------------------------------------------
define('CMS_NAME',    'CMS');
define('CMS_TAGLINE', 'Unified Content Management');

// ---------------------------------------------------------------------------
// Session
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
// Load core providers
// ---------------------------------------------------------------------------
// Load small mbstring polyfills when the ext/mbstring PHP extension
// is not available. This allows the CMS to run on environments where
// mbstring isn't installed. For full unicode support install mbstring.
if (file_exists(CMS_ROOT . '/core/mb_polyfill.php')) {
    require_once CMS_ROOT . '/core/mb_polyfill.php';
}
require_once CMS_ROOT . '/core/database.php';
require_once CMS_ROOT . '/core/module_loader.php';
