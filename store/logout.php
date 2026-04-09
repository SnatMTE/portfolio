<?php
/**
 * logout.php
 *
 * Destroys the admin session and redirects to the login page.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

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
