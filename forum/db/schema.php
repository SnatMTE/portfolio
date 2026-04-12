<?php
/**
 * db/schema.php
 *
 * Creates all database tables if they do not already exist.
 * Called once per request via config.php after the PDO connection is established.
 *
 * Tables
 * ------
 *   roles       - User roles (admin, user)
 *   users       - Registered accounts with hashed passwords
 *   categories  - Forum categories with ordering and descriptions
 *   threads     - Discussion threads, each belonging to a category
 *   posts       - Individual posts/replies within a thread
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Runs all CREATE TABLE statements and seeds default data.
 * Uses IF NOT EXISTS so repeated calls are completely safe.
 *
 * @param PDO $pdo  Active PDO/SQLite connection with foreign keys enabled.
 * @return void
 */
function initSchema(PDO $pdo): void
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
            role_id       INTEGER NOT NULL DEFAULT 2
                              REFERENCES roles(id),
            bio           TEXT    NOT NULL DEFAULT '',
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS categories (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL UNIQUE,
            slug          TEXT    NOT NULL UNIQUE,
            description   TEXT    NOT NULL DEFAULT '',
            display_order INTEGER NOT NULL DEFAULT 0,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS threads (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT    NOT NULL,
            slug        TEXT    NOT NULL UNIQUE,
            category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            user_id     INTEGER NOT NULL REFERENCES users(id)      ON DELETE CASCADE,
            is_sticky   INTEGER NOT NULL DEFAULT 0,
            is_locked   INTEGER NOT NULL DEFAULT 0,
            view_count  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS posts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            thread_id  INTEGER NOT NULL REFERENCES threads(id) ON DELETE CASCADE,
            user_id    INTEGER NOT NULL REFERENCES users(id)   ON DELETE CASCADE,
            content    TEXT    NOT NULL,
            is_deleted INTEGER NOT NULL DEFAULT 0,
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_threads_category ON threads(category_id);
        CREATE INDEX IF NOT EXISTS idx_threads_updated  ON threads(updated_at DESC);
        CREATE INDEX IF NOT EXISTS idx_posts_thread     ON posts(thread_id);
        CREATE INDEX IF NOT EXISTS idx_posts_user       ON posts(user_id);
    ");

    // Seed the two default roles on first run
    $roleCount = (int) $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    if ($roleCount === 0) {
        $pdo->exec("INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'user')");
    }
}

// Run immediately when included
initSchema(getDB());
