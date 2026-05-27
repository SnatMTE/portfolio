<?php
/**
 * crm/notes/delete.php
 *
 * Deletes a note after CSRF validation and returns to parent entity.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$id   = (int) ($_GET['id']   ?? 0);
$csrf = trim($_GET['csrf']   ?? '');

if ($id <= 0 || !crmValidateCsrf($csrf)) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(SITE_URL . '/crm/');
}

$db   = getCRMDB();
$stmt = $db->prepare("SELECT * FROM crm_notes WHERE id = :id");
$stmt->execute([':id' => $id]);
$note = $stmt->fetch();

if (!$note) {
    crmFlash('Note not found.', 'error');
    crmRedirect(SITE_URL . '/crm/');
}

// Editors may only delete their own notes; admins may delete any.
if (!cmsIsAdmin() && (int) $note['created_by'] !== (int) ($_SESSION['user_id'] ?? 0)) {
    crmFlash('You may not delete another user\'s note.', 'error');
    crmRedirect(SITE_URL . '/crm/' . $note['related_type'] . 's/view.php?id=' . $note['related_id']);
}

$db->prepare("DELETE FROM crm_notes WHERE id = :id")->execute([':id' => $id]);
crmLogActivity('deleted note', $note['related_type'], $note['related_id']);
crmFlash('Note deleted.', 'success');
crmRedirect(SITE_URL . '/crm/' . $note['related_type'] . 's/view.php?id=' . $note['related_id']);
