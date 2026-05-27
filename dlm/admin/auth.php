<?php
/**
 * admin/auth.php
 *
 * Authentication helpers for the Download Manager admin panel.
 * Every admin page must require this file before producing any output.
 *
 * Functions
 * ---------
 *   requireLogin()      – Redirects unauthenticated visitors to login.
 *   currentAdminUser()  – Returns the logged-in admin's basic data.
 *
 * Flash messaging lives in functions.php (flashMessage, getFlash, renderFlash).
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
 * Redirects visitors who are not logged in to the appropriate login page.
 *
 * In CMS mode the shared CMS login handles authentication.
 * In standalone mode the module's own login.php is used.
 *
 * @return void
 */
function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        $loginUrl = (defined('DM_CMS_MODE') && DM_CMS_MODE && defined('CMS_URL'))
            ? CMS_URL . '/login.php'
            : SITE_URL . '/login.php';
        redirect($loginUrl);
    }
}

// ---------------------------------------------------------------------------
// Current user helper
// ---------------------------------------------------------------------------

/**
 * Returns basic information about the currently authenticated admin.
 *
 * In CMS mode, the CMS session is the authority and we don't hit the
 * module's own users table (which doesn't exist in CMS mode anyway).
 *
 * @return array{id:int,username:string,email:string}|null
 */
function currentAdminUser(): ?array
{
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id === 0) {
        return null;
    }

    // CMS mode: trust the session without a database round-trip
    if (defined('DM_CMS_MODE') && DM_CMS_MODE) {
        return [
            'id'       => $id,
            'username' => $_SESSION['admin_username'] ?? ($_SESSION['username'] ?? 'Admin'),
            'email'    => '',
        ];
    }

    $stmt = getDB()->prepare('SELECT id, username, email FROM dm_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
