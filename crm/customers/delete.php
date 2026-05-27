<?php
/**
 * crm/customers/delete.php
 *
 * Deletes a customer after CSRF validation.
 * GET-based deletion is intentionally simple — the confirm dialog in the UI
 * is the user-facing safeguard; CSRF token is the server-side safeguard.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAdmin();

$id    = (int) ($_GET['id'] ?? 0);
$csrf  = trim($_GET['csrf'] ?? '');

if ($id <= 0 || !crmValidateCsrf($csrf)) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(CRM_URL . '/customers/');
}

$db   = getCRMDB();
$stmt = $db->prepare("SELECT first_name, last_name FROM crm_customers WHERE id = :id");
$stmt->execute([':id' => $id]);
$row  = $stmt->fetch();

if (!$row) {
    crmFlash('Customer not found.', 'error');
    crmRedirect(CRM_URL . '/customers/');
}

// Cascade-delete linked records that SQLite foreign key rules won't handle automatically
// because some relations use ON DELETE SET NULL.
$db->prepare("DELETE FROM crm_notes       WHERE related_type = 'customer' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_tasks       WHERE related_type = 'customer' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_follow_ups  WHERE related_type = 'customer' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_taggables   WHERE taggable_type = 'customer' AND taggable_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_attachments WHERE related_type = 'customer' AND related_id = :id")->execute([':id' => $id]);
$db->prepare("DELETE FROM crm_customers   WHERE id = :id")->execute([':id' => $id]);

$name = trim($row['first_name'] . ' ' . $row['last_name']);
crmLogActivity('deleted', 'customer', $id, $name);

crmFlash('Customer "' . $name . '" deleted.', 'success');
crmRedirect(CRM_URL . '/customers/');
