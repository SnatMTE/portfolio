<?php
/**
 * db/schema.php
 *
 * Creates Download Manager tables if they do not already exist.
 * Called automatically via config.php on every request.
 *
 * Tables
 * ------
 *   dm_users            – Standalone admin accounts (skipped in CMS mode).
 *   dm_downloads        – File records with metadata.
 *   dm_download_tokens  – Time-limited secure download tokens.
 *
 * The dm_ prefix prevents collisions when the module runs inside the CMS
 * and shares the cms.sqlite database.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Runs all CREATE TABLE statements for the Download Manager.
 *
 * In CMS mode the dm_users table is skipped because the CMS users table
 * serves the same purpose. Each statement uses IF NOT EXISTS so repeated
 * calls on every request are effectively a no-op.
 *
 * @param PDO $pdo  Active PDO connection with foreign keys enabled.
 *
 * @return void
 */
function initDownloadSchema(PDO $pdo): void
{
    // Only create a local users table when running standalone
    if (!defined('DM_CMS_MODE') || !DM_CMS_MODE) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS dm_users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                username   TEXT    NOT NULL UNIQUE,
                password   TEXT    NOT NULL,
                email      TEXT    NOT NULL UNIQUE,
                created_at TEXT    NOT NULL DEFAULT (datetime('now'))
            );
        ");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dm_downloads (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id        INTEGER,
            title          TEXT    NOT NULL,
            description    TEXT    NOT NULL DEFAULT '',
            file_path      TEXT    NOT NULL,
            original_name  TEXT    NOT NULL,
            file_size      INTEGER NOT NULL DEFAULT 0,
            mime_type      TEXT    NOT NULL DEFAULT '',
            category       TEXT    NOT NULL DEFAULT '',
            download_count INTEGER NOT NULL DEFAULT 0,
            visibility     TEXT    NOT NULL DEFAULT 'public'
                               CHECK(visibility IN ('public', 'private')),
            created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at     TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS dm_download_tokens (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            download_id INTEGER NOT NULL REFERENCES dm_downloads(id) ON DELETE CASCADE,
            token       TEXT    NOT NULL UNIQUE,
            expires_at  TEXT    NOT NULL,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_dm_downloads_visibility
            ON dm_downloads (visibility);

        CREATE INDEX IF NOT EXISTS idx_dm_downloads_category
            ON dm_downloads (category);

        CREATE INDEX IF NOT EXISTS idx_dm_tokens_token
            ON dm_download_tokens (token);
    ");
}

// Run immediately when this file is included
initDownloadSchema(getDB());
