<?php
/**
 * admin/auth.php
 *
 * Authentication helper for the admin panel.
 * Every admin page must include this file before any output.
 *
 * Functions
 * ---------
 *   requireLogin()     – Terminates with a redirect if no valid session exists.
 *   currentAdminUser() – Returns an array of the currently logged-in admin's data.
 *   flashMessage()     – Stores a one-time status message in the session.
 *   getFlash()         – Retrieves and clears the stored flash message.
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
 * Checks for the presence of a valid `admin_id` in the current session.
 * If the session value is absent or empty the user is redirected to
 * login.php and execution is halted.
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
// Session helpers
// ---------------------------------------------------------------------------

/**
 * Returns basic information about the currently authenticated admin user.
 *
 * Looks up the user record in the database using the session-stored ID.
 * Returns NULL if the session ID no longer corresponds to a valid user row.
 *
 * @return array<string, mixed>|null  Associative array with keys: id, username, email; or NULL.
 */
function currentAdminUser(): ?array
{
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id === 0) {
        return null;
    }
    // In CMS mode the blog users table is empty; return session-based data.
    if (defined('CMS_ROOT')) {
        return [
            'id'       => $id,
            'username' => $_SESSION['admin_username'] ?? ($_SESSION['username'] ?? ''),
            'email'    => '',
        ];
    }
    $stmt = getDB()->prepare("SELECT id, username, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Run the login check immediately when this file is included
requireLogin();
