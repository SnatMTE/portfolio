<?php
/**
 * cms/db/schema.php
 *
 * Creates all CMS core tables if they do not already exist.
 * Called once per request by core/database.php.
 *
 * Tables
 * ------
 *   roles    – User roles (admin, editor, user)
 *   users    – CMS accounts; shared by all installed modules
 *   settings – Key/value site settings
 *   pages    – Static content pages
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

/**
 * Runs all CREATE TABLE statements and seeds default data.
 *
 * @param PDO $pdo  Active PDO/SQLite connection.
 * @return void
 */
function initCMSSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT    NOT NULL UNIQUE
        );

        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT    NOT NULL UNIQUE,
            email         TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            role_id       INTEGER NOT NULL DEFAULT 3
                              REFERENCES roles(id),
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS pages (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT    NOT NULL,
            slug         TEXT    NOT NULL UNIQUE,
            content      TEXT    NOT NULL DEFAULT '',
            status       TEXT    NOT NULL DEFAULT 'draft'
                             CHECK(status IN ('published', 'draft')),
            show_in_menu INTEGER NOT NULL DEFAULT 0,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Add show_in_menu column to existing databases that predate this schema change
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN show_in_menu INTEGER NOT NULL DEFAULT 0");
    } catch (\PDOException $e) {
        // Column already exists – safe to ignore
    }

    // Seed roles on first run
    $roleCount = (int) $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    if ($roleCount === 0) {
        $pdo->exec("INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'editor'), (3, 'user')");
    }

    // Seed default settings on first run
    $settingsCount = (int) $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($settingsCount === 0) {
        $defaults = [
            ['site_name',    'My CMS'],
            ['site_tagline', 'Powered by CMS'],
            ['allow_reg',    '0'],
        ];
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
        foreach ($defaults as [$key, $val]) {
            $stmt->execute([':key' => $key, ':value' => $val]);
        }
    }

    // Seed a default Home page if none exists yet
    $homeExists = (int) $pdo->query("SELECT COUNT(*) FROM pages WHERE slug = 'home'")->fetchColumn();
    if ($homeExists === 0) {
        $pdo->exec(
            "INSERT INTO pages (title, slug, content, status, show_in_menu)
             VALUES ('Home', 'home', '<p>Welcome to your new CMS site. Edit this page from the admin panel.</p>', 'published', 1)"
        );
    }
}
