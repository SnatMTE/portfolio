<?php
/**
 * crm/leads/delete.php
 *
 * Deletes a lead after CSRF validation.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAdmin();

$id   = (int) ($_GET['id'] ?? 0);
$csrf = trim($_GET['csrf'] ?? '');

if ($id <= 0 || !crmValidateCsrf($csrf)) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(SITE_URL . '/crm/leads/');
}

$db   = getCRMDB();
$stmt = $db->prepare("SELECT title FROM crm_leads WHERE id = :id");
$stmt->execute([':id' => $id]);
$row  = $stmt->fetch();

if (!$row) {
    crmFlash('Lead not found.', 'error');
    crmRedirect(SITE_URL . '/crm/leads/');
}

$db->prepare("DELETE FROM crm_notes      WHERE related_type = 'lead' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_tasks      WHERE related_type = 'lead' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_follow_ups WHERE related_type = 'lead' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_taggables  WHERE taggable_type = 'lead' AND taggable_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_leads      WHERE id = :id")->execute([':id' => $id]);

crmLogActivity('deleted', 'lead', $id, $row['title']);
crmFlash('Lead "' . $row['title'] . '" deleted.', 'success');
crmRedirect(SITE_URL . '/crm/leads/');
