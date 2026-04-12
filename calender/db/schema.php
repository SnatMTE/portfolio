<?php
/**
 * db/schema.php
 *
 * Creates calendar-specific tables. Safe to call repeatedly.
 * Uses prefix `cal_` so tables coexist safely in a shared CMS database
 * without clashing with core CMS tables.
 *
 * Tables
 * ------
 *   cal_events   – Calendar events (title, times, location, visibility)
 *   cal_tokens   – Sync feed tokens for Google Calendar / Apple / Outlook
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Runs all CREATE TABLE statements against the given PDO connection.
 * Each statement uses "IF NOT EXISTS" so repeated calls are safe.
 *
 * @param PDO $pdo  An active PDO connection (SQLite).
 * @return void
 */
function initCalendarSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cal_events (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id        INTEGER,
            title          TEXT    NOT NULL,
            description    TEXT    NOT NULL DEFAULT '',
            start_datetime TEXT    NOT NULL,
            end_datetime   TEXT    NOT NULL,
            location       TEXT    NOT NULL DEFAULT '',
            is_public      INTEGER NOT NULL DEFAULT 1
                               CHECK(is_public IN (0, 1)),
            created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at     TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS cal_tokens (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER,
            token      TEXT    NOT NULL UNIQUE,
            label      TEXT    NOT NULL DEFAULT 'My Calendar',
            is_active  INTEGER NOT NULL DEFAULT 1
                           CHECK(is_active IN (0, 1)),
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Standalone-only: users table for admin auth when not in CMS mode.
    // In CMS mode this is a no-op because getCMSDB already has a users table.
    if (!defined('CMS_ROOT')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                username     TEXT    NOT NULL UNIQUE,
                email        TEXT    NOT NULL UNIQUE,
                password     TEXT    NOT NULL,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
            );
        ");
    }
}
