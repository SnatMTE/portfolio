<?php
/**
 * upload.php
 *
 * Admin-only file upload page.
 * Validates the submitted file and inserts a new dm_downloads record.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/core/upload_handler.php';

requireLogin();

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
        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please select a file to upload.';
        }

        if (empty($errors)) {
            try {
                $uploaded = handleUpload($_FILES['file']);

                $stmt = getDB()->prepare(
                    "INSERT INTO dm_downloads
                        (user_id, title, description, file_path, original_name,
                         file_size, mime_type, category, visibility)
                     VALUES
                        (:user_id, :title, :description, :file_path, :original_name,
                         :file_size, :mime_type, :category, :visibility)"
                );
                $stmt->execute([
                    ':user_id'       => $_SESSION['admin_id'] ?? null,
                    ':title'         => $title,
                    ':description'   => $desc,
                    ':file_path'     => $uploaded['file_path'],
                    ':original_name' => $uploaded['original_name'],
                    ':file_size'     => $uploaded['file_size'],
                    ':mime_type'     => $uploaded['mime_type'],
                    ':category'      => $category,
                    ':visibility'    => $visibility,
                ]);

                flashMessage('File uploaded successfully.', 'success');
                redirect(SITE_URL . '/admin/files.php');

            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Upload File';
$file      = null; // null tells the form template this is a new upload

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Upload – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php
    $currentAdminPage = 'upload';
    require_once __DIR__ . '/templates/admin_nav.php';
    ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>&#8679; Upload File</h1>
            <a href="<?= SITE_URL ?>/admin/files.php" class="btn btn--outline btn--sm">← All Files</a>
        </div>

        <div class="admin-form-card">
            <?php
            $formAction = SITE_URL . '/upload.php';
            require_once __DIR__ . '/templates/upload_form.php';
            ?>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
