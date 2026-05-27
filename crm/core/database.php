<?php
/**
 * crm/core/database.php
 *
 * Provides getCRMDB() — the singleton PDO connection to crm.sqlite.
 * Mirrors the CMS database.php pattern so the architecture is consistent.
 *
 * Migration note:
 *   To move to MySQL, replace the PDO DSN string, remove the PRAGMA calls,
 *   and adjust CRM_DB_FILE to a proper DSN constant.  Every query already
 *   uses prepared statements with named placeholders.
 */

/**
 * Returns the singleton PDO connection to crm.sqlite.
 * Creates the db/ directory and initialises the schema on first call.
 *
 * @return PDO
 */
function getCRMDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dbDir = dirname(CRM_DB_FILE);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $pdo = new PDO('sqlite:' . CRM_DB_FILE, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // WAL mode gives much better concurrent read performance.
            $pdo->exec('PRAGMA journal_mode=WAL');
            // Foreign key enforcement is off by default in SQLite.
            $pdo->exec('PRAGMA foreign_keys=ON');
        } catch (PDOException $e) {
            http_response_code(500);
            exit('CRM database connection failed.');
        }

        require_once CRM_ROOT . '/db/schema.php';
        initCRMSchema($pdo);

        // Seed demo data when the DEMO flag file is present.
        if (file_exists(CRM_ROOT . '/DEMO') && file_exists(CRM_ROOT . '/db/demo_seed.php')) {
            require_once CRM_ROOT . '/db/demo_seed.php';
            if (function_exists('seedDemoCRM')) {
                seedDemoCRM($pdo);
            }
        }
    }

    return $pdo;
}
