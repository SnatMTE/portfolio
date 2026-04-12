<?php
/**
 * cms/logout.php
 *
 * Destroys the shared CMS session and redirects to login.
 * Validates a CSRF token passed as ?csrf= to prevent forced-logout CSRF.
 *
 * Usage:
 *   <a href="<?= SITE_URL ?>/logout.php?csrf=<?= cmsCsrfToken() ?>">Log out</a>
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// Validate CSRF token (GET parameter)
$token = $_GET['csrf'] ?? '';
if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

// Clear all session data
$_SESSION = [];

// Invalidate session cookie
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
