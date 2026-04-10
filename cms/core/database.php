<?php
/**
 * cms/core/database.php
 *
 * Provides getCMSDB() — the shared PDO connection to the CMS SQLite database.
 * Included automatically by cms/config.php.
 *
 * Modules that run inside the CMS keep their own getDB() for content tables
 * and call getCMSDB() only when they need CMS user/settings data.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

/**
 * Returns the singleton PDO connection to cms.sqlite.
 * Creates the database directory and initialises the schema on first call.
 *
 * @return PDO
 */
function getCMSDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $isDemo = (isset($_GET['demo']) && $_GET['demo'] === '1') || file_exists(CMS_ROOT . '/DEMO');

        $dbDir = CMS_ROOT . '/db';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $pdo = new PDO('sqlite:' . CMS_DB_FILE, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
        } catch (PDOException $e) {
            http_response_code(500);
            exit('CMS database connection failed.');
        }

        require_once CMS_ROOT . '/db/schema.php';
        initCMSSchema($pdo);

        if ($isDemo && file_exists(CMS_ROOT . '/db/demo_seed.php')) {
            require_once CMS_ROOT . '/db/demo_seed.php';
            if (function_exists('seedDemoCMS')) {
                seedDemoCMS($pdo);
            }
        }
    }

    return $pdo;
}
