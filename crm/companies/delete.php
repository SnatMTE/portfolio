<?php
/**
 * crm/companies/delete.php
 *
 * Deletes a company after CSRF validation.
 * Unlinks customers and leads rather than deleting them.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAdmin();

$id   = (int) ($_GET['id']   ?? 0);
$csrf = trim($_GET['csrf']   ?? '');

if ($id <= 0 || !crmValidateCsrf($csrf)) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(SITE_URL . '/crm/companies/');
}

$db   = getCRMDB();
$stmt = $db->prepare("SELECT name FROM crm_companies WHERE id = :id");
$stmt->execute([':id' => $id]);
$row  = $stmt->fetch();

if (!$row) {
    crmFlash('Company not found.', 'error');
    crmRedirect(SITE_URL . '/crm/companies/');
}

// Unlink rather than cascade-delete to preserve customer and lead records.
$db->prepare("UPDATE crm_customers SET company_id = NULL WHERE company_id = :id")->execute([':id' => $id]);
$db->prepare("UPDATE crm_leads     SET company_id = NULL WHERE company_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_notes      WHERE related_type = 'company' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_taggables  WHERE taggable_type = 'company' AND taggable_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_companies  WHERE id = :id")->execute([':id' => $id]);

crmLogActivity('deleted', 'company', $id, $row['name']);
crmFlash('Company "' . $row['name'] . '" deleted.', 'success');
crmRedirect(SITE_URL . '/crm/companies/');
