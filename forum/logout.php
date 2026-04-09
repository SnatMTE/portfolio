<?php
/**
 * logout.php
 *
 * Destroys the current session and redirects to the homepage.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Start a session if one exists so it can be destroyed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wipe session data and invalidate the session cookie
$_SESSION = [];

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

redirect(SITE_URL . '/login.php');
