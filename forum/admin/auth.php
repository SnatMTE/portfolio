<?php
/**
 * admin/auth.php
 *
 * Authentication and session helpers for the admin panel.
 * Every admin page must include this file before producing any output.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

/**
 * Redirects to login if the visitor is not an authenticated admin.
 * Call this at the top of every admin page.
 *
 * @return void
 */
function requireAdminAuth(): void
{
    if (!isLoggedIn()) {
        flashMessage('Please log in to access the admin panel.', 'error');
        $loginUrl = defined('CMS_URL') ? CMS_URL . '/login.php' : SITE_URL . '/login.php';
        redirect($loginUrl);
    }
    if (!isAdmin()) {
        http_response_code(403);
        exit('Access denied: administrator privileges required.');
    }
}

// ---------------------------------------------------------------------------
// Admin stats helper
// ---------------------------------------------------------------------------

/**
 * Returns an array of high-level dashboard statistics.
 *
 * @return array{categories: int, threads: int, posts: int, users: int}
 */
function getAdminStats(): array
{
    $db = getDB();
    return [
        'categories' => (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'threads'    => (int) $db->query("SELECT COUNT(*) FROM threads")->fetchColumn(),
        'posts'      => (int) $db->query("SELECT COUNT(*) FROM posts WHERE is_deleted = 0")->fetchColumn(),
        'users'      => (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    ];
}
