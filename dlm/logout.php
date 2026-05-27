<?php
/**
 * logout.php
 *
 * Destroys the admin session and redirects to the login page.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy session data and invalidate the cookie
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

// In CMS mode, hand off to CMS logout
if (defined('DM_CMS_MODE') && DM_CMS_MODE) {
    $cmsLogout = defined('CMS_URL') ? CMS_URL . '/logout.php' : '../logout.php';
    header('Location: ' . $cmsLogout);
    exit;
}

header('Location: ' . SITE_URL . '/login.php');
exit;
