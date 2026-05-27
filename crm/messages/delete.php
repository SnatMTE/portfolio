<?php
/**
 * crm/messages/delete.php
 *
 * Deletes a message (admin only, with CSRF).
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAdmin();

$id   = (int) ($_GET['id']   ?? 0);
$csrf = trim($_GET['csrf']   ?? '');

if ($id <= 0 || !crmValidateCsrf($csrf)) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(SITE_URL . '/crm/messages/');
}

$db = getCRMDB();
$db->prepare("DELETE FROM crm_messages WHERE id = :id")->execute([':id' => $id]);

crmFlash('Message deleted.', 'success');
crmRedirect(SITE_URL . '/crm/messages/');
