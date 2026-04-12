<?php
/**
 * admin/auth.php
 *
 * Authentication helper for the store admin panel.
 * Every admin page must include this file before any output.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

/**
 * Redirects unauthenticated visitors to the login page.
 *
 * @return void
 */
function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        // In CMS mode, redirect to the shared CMS login; otherwise own login
        $loginUrl = defined('CMS_URL') ? CMS_URL . '/login.php' : SITE_URL . '/login.php';
        redirect($loginUrl);
    }
}

// ---------------------------------------------------------------------------
// Current user
// ---------------------------------------------------------------------------

/**
 * Returns data for the currently authenticated admin user, or NULL.
 *
 * @return array<string, mixed>|null
 */
function currentAdminUser(): ?array
{
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id === 0) return null;

    // In CMS mode the store users table is empty; return session-based data.
    if (defined('CMS_ROOT')) {
        return [
            'id'       => $id,
            'username' => $_SESSION['admin_username'] ?? ($_SESSION['username'] ?? ''),
            'email'    => '',
        ];
    }

    $stmt = getDB()->prepare("SELECT id, username, email FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ---------------------------------------------------------------------------
// Boot: require login on every admin include
// ---------------------------------------------------------------------------
requireLogin();
