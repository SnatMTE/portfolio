<?php
/**
 * crm/logout.php
 *
 * Ends the standalone CRM session and redirects to the login page.
 * In CMS-integrated mode this page is unused — the CMS logout handles sign-out.
 */

require_once __DIR__ . '/config.php';

// Clear only the CRM-specific session keys so we don't disrupt any other
// module's session data that may share the same PHP session.
unset(
    $_SESSION['crm_user_id'],
    $_SESSION['crm_username'],
    $_SESSION['crm_role'],
    $_SESSION['crm_csrf_token'],
    $_SESSION['crm_flash']
);

header('Location: ' . CRM_URL . '/login.php');
exit;
