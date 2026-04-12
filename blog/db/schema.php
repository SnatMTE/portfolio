<?php
/**
 * db/schema.php
 *
 * Creates all database tables if they do not already exist.
 * Called once on every request via config.php; SQLite "CREATE TABLE IF NOT EXISTS"
 * ensures this is effectively a no-op after the first run.
 *
 * Tables
 * ------
 *   users       – Admin accounts with hashed passwords
 *   categories  – Post categories
 *   tags        – Post tags
 *   posts       – Blog posts, linked to a user and optional category
 *   post_tags   – Many-to-many join between posts and tags
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Runs all CREATE TABLE statements against the supplied PDO connection.
 *
 * Each statement uses "IF NOT EXISTS" so repeated calls are safe.
 *
 * @param PDO $pdo  An active PDO/SQLite connection with FK enforcement on.
 *
 * @return void
 */
function initSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT    NOT NULL UNIQUE,
            password     TEXT    NOT NULL,
            email        TEXT    NOT NULL UNIQUE,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS categories (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT    NOT NULL UNIQUE,
            slug       TEXT    NOT NULL UNIQUE,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS tags (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT    NOT NULL UNIQUE,
            slug       TEXT    NOT NULL UNIQUE,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS posts (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            title          TEXT    NOT NULL,
            slug           TEXT    NOT NULL UNIQUE,
            content        TEXT    NOT NULL DEFAULT '',
            excerpt        TEXT    NOT NULL DEFAULT '',
            featured_image TEXT,
            author_id      INTEGER NOT NULL REFERENCES users(id)       ON DELETE CASCADE,
            category_id    INTEGER          REFERENCES categories(id)  ON DELETE SET NULL,
            status         TEXT    NOT NULL DEFAULT 'draft'
                               CHECK(status IN ('published','draft')),
            created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at     TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS post_tags (
            post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
            tag_id  INTEGER NOT NULL REFERENCES tags(id)  ON DELETE CASCADE,
            PRIMARY KEY (post_id, tag_id)
        );
    ");
}

// Run schema initialisation immediately when this file is included
initSchema(getDB());
