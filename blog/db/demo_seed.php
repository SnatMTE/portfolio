<?php
/**
 * blog/db/demo_seed.php
 *
 * Seeds demo categories, users, tags and posts into an in-memory blog database
 * for screenshot/demo purposes.
 */

function seedDemoBlog(PDO $pdo): void
{
    // Categories
    $pdo->exec("INSERT OR IGNORE INTO categories (id, name, slug, created_at) VALUES
        (2000, 'Tech', 'tech', datetime('now', '-14 days')),
        (2001, 'Tutorials', 'tutorials', datetime('now', '-13 days')),
        (2002, 'Personal', 'personal', datetime('now', '-12 days'))");

    // Tags
    $pdo->exec("INSERT OR IGNORE INTO tags (id, name, slug, created_at) VALUES
        (3000, 'php', 'php', datetime('now', '-11 days')),
        (3001, 'sqlite', 'sqlite', datetime('now', '-10 days')),
        (3002, 'howto', 'howto', datetime('now', '-9 days'))");

    // Users
    $pdo->exec("INSERT OR IGNORE INTO users (id, username, password, email, created_at) VALUES
        (1, 'admin', '', 'admin@example.local', datetime('now', '-30 days')),
        (2000, 'alice', '', 'alice@example.local', datetime('now', '-20 days')),
        (2001, 'bob', '', 'bob@example.local', datetime('now', '-18 days'))");

    // Posts
    $pdo->exec("INSERT OR IGNORE INTO posts (id, title, slug, content, excerpt, featured_image, author_id, category_id, status, created_at, updated_at) VALUES
        (4000, 'Getting started with the project', 'getting-started', '<p>This is a demo post about starting the project.</p>', 'Demo excerpt: getting started.', NULL, 2000, 2000, 'published', datetime('now', '-7 days'), datetime('now', '-5 days')),
        (4001, 'How to use SQLite with PHP', 'sqlite-with-php', '<p>Short guide demonstrating SQLite usage in PHP.</p>', 'Demo excerpt: SQLite + PHP', NULL, 2001, 2000, 'published', datetime('now', '-6 days'), datetime('now', '-3 days')),
        (4002, 'Welcome to the blog', 'welcome', '<p>Welcome! This blog contains demo posts for screenshots.</p>', 'Welcome post excerpt', NULL, 1, 2002, 'published', datetime('now', '-10 days'), datetime('now', '-9 days'))");

    // Link tags to posts
    $pdo->exec("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES
        (4001, 3000),
        (4001, 3001),
        (4000, 3002)");
}
