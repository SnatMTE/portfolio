<?php
/**
 * logout.php
 *
 * Destroys the admin session and redirects to the login page.
 *
 * Validates a CSRF token passed as a GET parameter to prevent CSRF-based
 * forced logout attacks.  Also clears the session cookie for completeness.
 *
 * Usage:
 *   <a href="/logout.php?csrf=<?= csrfToken() ?>">Log out</a>
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// When inside CMS, delegate logout to the shared CMS logout page
if (defined('CMS_ROOT')) {
    $cmsLogout = defined('CMS_URL') ? CMS_URL . '/logout.php' : '../logout.php';
    $csrf = $_GET['csrf'] ?? '';
    header('Location: ' . $cmsLogout . '?csrf=' . urlencode($csrf));
    exit;
}

// Validate CSRF to prevent forced-logout via CSRF
$token = $_GET['csrf'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    // Simply redirect home if CSRF fails rather than showing an error
    header('Location: ' . SITE_URL);
    exit;
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ' . SITE_URL . '/login.php');
exit;
