<?php
/**
 * admin/auth.php
 *
 * Authentication helper for the Calendar admin panel.
 * Included at the top of every admin page.
 *
 * Handles both standalone (admin_id session) and CMS-integrated
 * (user_id + role session) modes.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once dirname(__DIR__) . '/functions.php';

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

/**
 * Redirects unauthenticated visitors to the login page.
 * In CMS mode, redirects to the shared CMS login.
 *
 * @return void
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        if (defined('CMS_URL')) {
            redirect(CMS_URL . '/login.php');
        } else {
            redirect(SITE_URL . '/login.php');
        }
    }
}

// ---------------------------------------------------------------------------
// Flash message helpers (aliases for admin pages)
// ---------------------------------------------------------------------------

/**
 * Stores a flash message in the session (admin wrapper).
 *
 * @param string $msg
 * @param string $type  'success' | 'error'
 */
function setFlash(string $msg, string $type = 'success'): void
{
    flashMessage($msg, $type);
}

// Immediately require a valid login for any file that requires this one.
requireLogin();
