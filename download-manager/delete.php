<?php
/**
 * delete.php
 *
 * Handles POST-only deletion of a download record and its stored file.
 * GET requests redirect to the admin files list.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/core/file_helper.php';

requireLogin();

// Only process POST — GET is not a valid deletion method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/files.php');
}

if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
    flashMessage('Invalid request. Deletion cancelled.', 'error');
    redirect(SITE_URL . '/admin/files.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id) {
    flashMessage('Invalid file ID.', 'error');
    redirect(SITE_URL . '/admin/files.php');
}

$file = getDownload($id);

if ($file === null) {
    flashMessage('File not found.', 'error');
    redirect(SITE_URL . '/admin/files.php');
}

// Remove the physical file first, then the database record
deleteStoredFile($file['file_path']);

$stmt = getDB()->prepare('DELETE FROM dm_downloads WHERE id = :id');
$stmt->execute([':id' => $id]);

flashMessage('"' . $file['title'] . '" has been deleted.', 'success');
redirect(SITE_URL . '/admin/files.php');
