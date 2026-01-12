<?php
/**
 * crm/db/schema.php
 *
 * Creates all CRM tables if they do not already exist.
 * Called once per request by core/database.php.
 *
 * Tables
 * ------
 *   crm_companies        – Organisations linked to customers/leads
 *   crm_customers        – Individual contact records
 *   crm_leads            – Sales pipeline entries
 *   crm_tasks            – Actionable tasks with due dates
 *   crm_notes            – Notes/communication log entries
 *   crm_messages         – Internal staff messaging
 *   crm_tags             – Flexible tag labels
 *   crm_taggables        – Polymorphic tag assignments
 *   crm_attachments      – File attachments
 *   crm_activity_log     – Audit trail of all actions
 *   crm_email_log        – Outbound email records
 *   crm_follow_ups       – Scheduled follow-up reminders
 *
 * All TEXT datetime columns store UTC values via SQLite's datetime('now').
 * Column names are deliberately generic so MySQL migration is a copy-paste
 * exercise: swap TEXT datetime → DATETIME, INTEGER PK → INT AUTO_INCREMENT.
 */

/**
 * Runs all CREATE TABLE statements for the CRM schema.
 *
 * @param PDO $pdo  Active PDO connection to crm.sqlite.
 * @return void
 */
function initCRMSchema(PDO $pdo): void
{
    // Companies — organisations that customers/leads belong to.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_companies (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT    NOT NULL,
            website     TEXT    NOT NULL DEFAULT '',
            phone       TEXT    NOT NULL DEFAULT '',
            email       TEXT    NOT NULL DEFAULT '',
            address     TEXT    NOT NULL DEFAULT '',
            city        TEXT    NOT NULL DEFAULT '',
            country     TEXT    NOT NULL DEFAULT '',
            industry    TEXT    NOT NULL DEFAULT '',
            notes       TEXT    NOT NULL DEFAULT '',
            status      TEXT    NOT NULL DEFAULT 'active'
                            CHECK(status IN ('active', 'inactive')),
            created_by  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Customers — individual contacts; optionally linked to a company.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_customers (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            company_id   INTEGER REFERENCES crm_companies(id) ON DELETE SET NULL,
            cms_user_id  INTEGER,
            first_name   TEXT    NOT NULL DEFAULT '',
            last_name    TEXT    NOT NULL DEFAULT '',
            email        TEXT    NOT NULL DEFAULT '',
            phone        TEXT    NOT NULL DEFAULT '',
            mobile       TEXT    NOT NULL DEFAULT '',
            job_title    TEXT    NOT NULL DEFAULT '',
            address      TEXT    NOT NULL DEFAULT '',
            city         TEXT    NOT NULL DEFAULT '',
            country      TEXT    NOT NULL DEFAULT '',
            avatar_url   TEXT    NOT NULL DEFAULT '',
            status       TEXT    NOT NULL DEFAULT 'active'
                             CHECK(status IN ('active', 'inactive', 'prospect', 'churned')),
            source       TEXT    NOT NULL DEFAULT '',
            assigned_to  INTEGER,
            created_by   INTEGER NOT NULL DEFAULT 0,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");
}
