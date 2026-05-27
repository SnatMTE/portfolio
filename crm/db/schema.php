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

    // Leads — sales pipeline entries that may eventually convert to customers.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_leads (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT    NOT NULL,
            customer_id  INTEGER REFERENCES crm_customers(id) ON DELETE SET NULL,
            company_id   INTEGER REFERENCES crm_companies(id) ON DELETE SET NULL,
            contact_name TEXT    NOT NULL DEFAULT '',
            contact_email TEXT   NOT NULL DEFAULT '',
            contact_phone TEXT   NOT NULL DEFAULT '',
            value        REAL    NOT NULL DEFAULT 0,
            currency     TEXT    NOT NULL DEFAULT 'GBP',
            stage        TEXT    NOT NULL DEFAULT 'new'
                             CHECK(stage IN ('new','contacted','qualified','proposal','negotiation','won','lost')),
            priority     TEXT    NOT NULL DEFAULT 'medium'
                             CHECK(priority IN ('low','medium','high','urgent')),
            source       TEXT    NOT NULL DEFAULT '',
            close_date   TEXT,
            assigned_to  INTEGER,
            created_by   INTEGER NOT NULL DEFAULT 0,
            notes        TEXT    NOT NULL DEFAULT '',
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Tasks — actionable items; can be linked to a customer, lead, or company.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_tasks (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            title         TEXT    NOT NULL,
            description   TEXT    NOT NULL DEFAULT '',
            related_type  TEXT    NOT NULL DEFAULT ''
                              CHECK(related_type IN ('','customer','lead','company')),
            related_id    INTEGER NOT NULL DEFAULT 0,
            status        TEXT    NOT NULL DEFAULT 'pending'
                              CHECK(status IN ('pending','in_progress','completed','cancelled')),
            priority      TEXT    NOT NULL DEFAULT 'medium'
                              CHECK(priority IN ('low','medium','high','urgent')),
            due_date      TEXT,
            assigned_to   INTEGER,
            created_by    INTEGER NOT NULL DEFAULT 0,
            completed_at  TEXT,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Notes — freeform log entries on customers, leads, or companies.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_notes (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            related_type  TEXT    NOT NULL DEFAULT ''
                              CHECK(related_type IN ('customer','lead','company','task')),
            related_id    INTEGER NOT NULL DEFAULT 0,
            content       TEXT    NOT NULL,
            is_private    INTEGER NOT NULL DEFAULT 0,
            created_by    INTEGER NOT NULL DEFAULT 0,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Internal messages between CRM users.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_messages (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id    INTEGER NOT NULL DEFAULT 0,
            recipient_id INTEGER NOT NULL DEFAULT 0,
            subject      TEXT    NOT NULL DEFAULT '',
            body         TEXT    NOT NULL,
            is_read      INTEGER NOT NULL DEFAULT 0,
            parent_id    INTEGER REFERENCES crm_messages(id) ON DELETE CASCADE,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Tags — reusable labels applied to any entity.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_tags (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT    NOT NULL UNIQUE,
            colour     TEXT    NOT NULL DEFAULT '#6b7280',
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Polymorphic join: any entity can have multiple tags.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_taggables (
            tag_id        INTEGER NOT NULL REFERENCES crm_tags(id) ON DELETE CASCADE,
            taggable_type TEXT    NOT NULL,
            taggable_id   INTEGER NOT NULL,
            PRIMARY KEY (tag_id, taggable_type, taggable_id)
        );
    ");

    // Attachments — files uploaded against a customer, lead, or company.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_attachments (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            related_type  TEXT    NOT NULL DEFAULT '',
            related_id    INTEGER NOT NULL DEFAULT 0,
            original_name TEXT    NOT NULL,
            stored_name   TEXT    NOT NULL UNIQUE,
            mime_type     TEXT    NOT NULL DEFAULT '',
            file_size     INTEGER NOT NULL DEFAULT 0,
            uploaded_by   INTEGER NOT NULL DEFAULT 0,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Activity log — append-only audit trail; never updated, only inserted.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_activity_log (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL DEFAULT 0,
            action       TEXT    NOT NULL,
            entity_type  TEXT    NOT NULL DEFAULT '',
            entity_id    INTEGER NOT NULL DEFAULT 0,
            description  TEXT    NOT NULL DEFAULT '',
            ip_address   TEXT    NOT NULL DEFAULT '',
            created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Email log — record of emails sent via the CRM to contacts.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_email_log (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id  INTEGER REFERENCES crm_customers(id) ON DELETE SET NULL,
            lead_id      INTEGER REFERENCES crm_leads(id) ON DELETE SET NULL,
            sent_by      INTEGER NOT NULL DEFAULT 0,
            recipient    TEXT    NOT NULL,
            subject      TEXT    NOT NULL,
            body         TEXT    NOT NULL DEFAULT '',
            status       TEXT    NOT NULL DEFAULT 'sent'
                             CHECK(status IN ('sent','failed','bounced')),
            sent_at      TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Follow-ups — scheduled reminders tied to a customer or lead.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_follow_ups (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            related_type  TEXT    NOT NULL DEFAULT ''
                              CHECK(related_type IN ('customer','lead')),
            related_id    INTEGER NOT NULL DEFAULT 0,
            assigned_to   INTEGER NOT NULL DEFAULT 0,
            due_at        TEXT    NOT NULL,
            note          TEXT    NOT NULL DEFAULT '',
            is_done       INTEGER NOT NULL DEFAULT 0,
            created_by    INTEGER NOT NULL DEFAULT 0,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Indexes — speed up the most common lookups without hurting write performance.
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_crm_customers_company   ON crm_customers(company_id)",
        "CREATE INDEX IF NOT EXISTS idx_crm_customers_assigned  ON crm_customers(assigned_to)",
        "CREATE INDEX IF NOT EXISTS idx_crm_customers_status    ON crm_customers(status)",
        "CREATE INDEX IF NOT EXISTS idx_crm_leads_stage         ON crm_leads(stage)",
        "CREATE INDEX IF NOT EXISTS idx_crm_leads_assigned      ON crm_leads(assigned_to)",
        "CREATE INDEX IF NOT EXISTS idx_crm_tasks_due           ON crm_tasks(due_date)",
        "CREATE INDEX IF NOT EXISTS idx_crm_tasks_assigned      ON crm_tasks(assigned_to)",
        "CREATE INDEX IF NOT EXISTS idx_crm_tasks_status        ON crm_tasks(status)",
        "CREATE INDEX IF NOT EXISTS idx_crm_notes_related       ON crm_notes(related_type, related_id)",
        "CREATE INDEX IF NOT EXISTS idx_crm_messages_recipient  ON crm_messages(recipient_id, is_read)",
        "CREATE INDEX IF NOT EXISTS idx_crm_activity_user       ON crm_activity_log(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_crm_activity_entity     ON crm_activity_log(entity_type, entity_id)",
        "CREATE INDEX IF NOT EXISTS idx_crm_followups_due       ON crm_follow_ups(due_at, is_done)",
        "CREATE INDEX IF NOT EXISTS idx_crm_taggables           ON crm_taggables(taggable_type, taggable_id)",
    ];
    foreach ($indexes as $sql) {
        $pdo->exec($sql);
    }

    // -----------------------------------------------------------------------
    // Standalone-mode user tables
    //
    // These are used when the CRM runs without a parent CMS.
    // In CMS-integrated mode they exist but are simply never queried —
    // getCMSDB() returns the CMS database instead.
    //
    // A 'users' VIEW matches the column names expected by crmGetUsers() and
    // crmUsername() so those helpers work without modification in either mode.
    // -----------------------------------------------------------------------

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_roles (
            id   INTEGER PRIMARY KEY,
            name TEXT    NOT NULL UNIQUE
        );
    ");

    // Seed the three built-in roles once.
    $pdo->exec("
        INSERT OR IGNORE INTO crm_roles (id, name) VALUES
            (1, 'admin'),
            (2, 'editor'),
            (3, 'user');
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT    NOT NULL UNIQUE,
            email         TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            role_id       INTEGER NOT NULL DEFAULT 3
                              REFERENCES crm_roles(id),
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );
    ");

    // VIEW: 'users' — exposes crm_users columns under the names that
    // getCMSDB() queries expect (id, username, email).
    // DROP + RECREATE is safe because CREATE VIEW IF NOT EXISTS cannot
    // update the column list if the schema changes.
    $pdo->exec("
        CREATE VIEW IF NOT EXISTS users AS
            SELECT id, username, email, role_id FROM crm_users;
    ");
}
