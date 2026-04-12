<?php
/**
 * cms/db/demo_seed.php
 *
 * Seeds demo CMS pages and users into an in-memory CMS database for screenshots.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

function seedDemoCMS(PDO $pdo): void
{
    // Users (editor + simple user)
    $pdo->exec("INSERT OR IGNORE INTO users (id, username, email, password_hash, role_id, created_at) VALUES
        (1000, 'editor', 'editor@example.local', '', 2, datetime('now', '-20 days')),
        (1001, 'janedoe', 'jane@example.local', '', 3, datetime('now', '-15 days'))");

    // Pages
    $pdo->exec("INSERT OR IGNORE INTO pages (id, title, slug, content, status, show_in_menu, created_at) VALUES
        (1000, 'About', 'about', '<p>This is the about page used for screenshots.</p>', 'published', 1, datetime('now', '-10 days')),
        (1001, 'Blog', 'blog', '<p>Sample blog landing page.</p>', 'published', 1, datetime('now', '-9 days')),
        (1002, 'Contact', 'contact', '<p>Contact info goes here.</p>', 'published', 1, datetime('now', '-8 days'))");
}
