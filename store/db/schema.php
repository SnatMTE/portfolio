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
 *   users        – Admin accounts with hashed passwords
 *   categories   – Product categories
 *   products     – Products available in the store
 *   orders       – Customer orders with payment status
 *   order_items  – Line items belonging to an order
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Runs all CREATE TABLE statements against the supplied PDO connection.
 *
 * @param PDO $pdo  An active PDO/SQLite connection.
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

        CREATE TABLE IF NOT EXISTS products (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            slug          TEXT    NOT NULL UNIQUE,
            description   TEXT    NOT NULL DEFAULT '',
            short_desc    TEXT    NOT NULL DEFAULT '',
            price         REAL    NOT NULL DEFAULT 0.00,
            stock         INTEGER NOT NULL DEFAULT 0,
            image         TEXT,
            category_id   INTEGER REFERENCES categories(id) ON DELETE SET NULL,
            status        TEXT    NOT NULL DEFAULT 'active'
                              CHECK(status IN ('active', 'inactive')),
            created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS orders (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name      TEXT    NOT NULL,
            customer_email     TEXT    NOT NULL,
            total              REAL    NOT NULL DEFAULT 0.00,
            status             TEXT    NOT NULL DEFAULT 'pending'
                                   CHECK(status IN ('pending','paid','cancelled','refunded')),
            payment_provider   TEXT    NOT NULL DEFAULT 'paypal',
            payment_id         TEXT,
            payment_detail     TEXT,
            created_at         TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at         TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id    INTEGER NOT NULL REFERENCES orders(id)   ON DELETE CASCADE,
            product_id  INTEGER          REFERENCES products(id) ON DELETE SET NULL,
            product_name TEXT   NOT NULL,
            price       REAL    NOT NULL,
            quantity    INTEGER NOT NULL DEFAULT 1
        );
    ");
}

// Run schema initialisation immediately when this file is included.
initSchema(getDB());
