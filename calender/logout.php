<?php
/**
 * calendar/logout.php
 *
 * Destroys the admin session (standalone mode only).
 * In CMS mode, redirects to the shared CMS logout.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

if (defined('CMS_ROOT')) {
    $cmsLogout = defined('CMS_URL') ? CMS_URL . '/logout.php' : '../logout.php';
    redirect($cmsLogout);
}

// Destroy session data
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
