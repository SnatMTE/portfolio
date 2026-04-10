<?php
/**
 * forum/db/demo_seed.php
 *
 * Seeds demo content into an in-memory forum database for screenshots.
 */

function seedDemoForum(PDO $pdo): void
{
    // Categories
    $pdo->exec("INSERT OR IGNORE INTO categories (id, name, slug, description, display_order) VALUES
        (1000, 'General', 'general', 'General discussion', 1),
        (1001, 'Announcements', 'announcements', 'News and announcements', 0)");

    // Users (admin is usually present but insert OR IGNORE just in case)
    $pdo->exec("INSERT OR IGNORE INTO users (id, username, email, password_hash, role_id) VALUES
        (1, 'admin', 'admin@example.local', '', 1),
        (1000, 'alice', 'alice@example.local', '', 2),
        (1001, 'bob', 'bob@example.local', '', 2)");

    // Threads
    $pdo->exec("INSERT OR IGNORE INTO threads (id, title, slug, category_id, user_id, is_sticky, view_count, created_at, updated_at) VALUES
        (1000, 'Welcome to the forum', 'welcome-to-the-forum', 1001, 1, 1, 123, datetime('now', '-7 days'), datetime('now', '-2 days')),
        (1001, 'Introduce yourself', 'introduce-yourself', 1000, 1000, 0, 45, datetime('now', '-6 days'), datetime('now', '-3 days'))");

    // Posts
    $pdo->exec("INSERT OR IGNORE INTO posts (id, thread_id, user_id, content, created_at) VALUES
        (1000, 1000, 1, 'Welcome everyone — feel free to ask questions here.', datetime('now', '-7 days')),
        (1001, 1000, 1000, 'Thanks! Happy to be here.', datetime('now', '-6 days')),
        (1002, 1001, 1000, 'Hi I am Alice, nice to meet you!', datetime('now', '-6 days')),
        (1003, 1001, 1001, 'Hello Alice, welcome!', datetime('now', '-5 days'))");
}
