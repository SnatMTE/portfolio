<?php
/**
 * edit.php
 *
 * Admin-only page for editing a download record's metadata.
 * Optionally replaces the stored file while preserving download count stats.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/core/upload_handler.php';
require_once __DIR__ . '/core/file_helper.php';

requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id) {
    http_response_code(400);
    exit('Invalid request.');
}

$file = getDownload($id);
if ($file === null) {
    http_response_code(404);
    exit('File not found.');
}

$errors     = [];
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $title      = trim($_POST['title']       ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $category   = trim($_POST['category']    ?? '');
        $visibility = $_POST['visibility']       ?? 'public';

        if ($title === '') {
            $errors[] = 'Title is required.';
        }
        if (!in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'public';
        }

        // Check if a replacement file was submitted
        $newUpload  = null;
        $hasNewFile = isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasNewFile) {
            try {
                $newUpload = handleUpload($_FILES['file']);
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            if ($newUpload !== null) {
                // Replace file on disk and update all file-related columns
                deleteStoredFile($file['file_path']);

                $stmt = getDB()->prepare(
                    "UPDATE dm_downloads SET
                        title = :title, description = :description, category = :category,
                        visibility = :visibility, file_path = :file_path,
                        original_name = :original_name, file_size = :file_size,
                        mime_type = :mime_type, updated_at = datetime('now')
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':title'         => $title,
                    ':description'   => $desc,
                    ':category'      => $category,
                    ':visibility'    => $visibility,
                    ':file_path'     => $newUpload['file_path'],
                    ':original_name' => $newUpload['original_name'],
                    ':file_size'     => $newUpload['file_size'],
                    ':mime_type'     => $newUpload['mime_type'],
                    ':id'            => $id,
                ]);
            } else {
                // Metadata-only update — keep the existing file
                $stmt = getDB()->prepare(
                    "UPDATE dm_downloads SET
                        title = :title, description = :description, category = :category,
                        visibility = :visibility, updated_at = datetime('now')
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':title'       => $title,
                    ':description' => $desc,
                    ':category'    => $category,
                    ':visibility'  => $visibility,
                    ':id'          => $id,
                ]);
            }

            flashMessage('File updated successfully.', 'success');
            redirect(SITE_URL . '/admin/files.php');
        }

        // Re-read the record so the form shows current values on error
        $file = getDownload($id);
    }
}

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Edit – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php
    $currentAdminPage = 'files';
    require_once __DIR__ . '/templates/admin_nav.php';
    ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>&#9998; Edit: <?= e($file['title']) ?></h1>
            <a href="<?= SITE_URL ?>/admin/files.php" class="btn btn--outline btn--sm">← All Files</a>
        </div>

        <div class="admin-form-card">
            <?php
            $formAction = SITE_URL . '/edit.php?id=' . $id;
            require_once __DIR__ . '/templates/upload_form.php';
            ?>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
