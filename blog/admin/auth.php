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

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------

/**
 * Stores a one-time "flash" message and its type in the session.
 *
 * The message is consumed by getFlash() on the next page load and then
 * automatically cleared from the session.
 *
 * @param string $message  The message text to show.
 * @param string $type     Alert type: 'success' | 'error' | 'info'.
 *
 * @return void
 */
function flashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieves the stored flash message and removes it from the session.
 *
 * Returns NULL if no flash message has been set.
 *
 * @return array{message: string, type: string}|null  The flash data or NULL.
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Renders a flash message as an HTML alert div if one exists.
 *
 * Reads and clears the flash message then outputs the appropriate HTML.
 * Intended to be called once at the top of each admin page's content area.
 *
 * @return void
 */
function renderFlash(): void
{
    $flash = getFlash();
    if ($flash) {
        echo '<div class="alert alert--' . e($flash['type']) . '" role="alert">' . e($flash['message']) . '</div>';
    }
}

// Run the login check immediately when this file is included
requireLogin();
