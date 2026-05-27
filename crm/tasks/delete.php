<?php
/**
 * crm/tasks/delete.php
 *
 * Deletes a task after CSRF validation.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAdmin();

$id   = (int) ($_GET['id'] ?? 0);
$csrf = trim($_GET['csrf'] ?? '');

if ($id <= 0 || !crmValidateCsrf($csrf)) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(SITE_URL . '/crm/tasks/');
}

$db   = getCRMDB();
$stmt = $db->prepare("SELECT title, related_type, related_id FROM crm_tasks WHERE id = :id");
$stmt->execute([':id' => $id]);
$row  = $stmt->fetch();

if (!$row) {
    crmFlash('Task not found.', 'error');
    crmRedirect(SITE_URL . '/crm/tasks/');
}

$db->prepare("DELETE FROM crm_tasks WHERE id = :id")->execute([':id' => $id]);
crmLogActivity('deleted', 'task', $id, $row['title']);
crmFlash('Task deleted.', 'success');

// Bounce back to parent entity if one was linked.
if ($row['related_type'] && $row['related_id']) {
    crmRedirect(SITE_URL . '/crm/' . $row['related_type'] . 's/view.php?id=' . $row['related_id']);
}
crmRedirect(SITE_URL . '/crm/tasks/');
