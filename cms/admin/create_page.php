<?php
/**
 * cms/admin/create_page.php  —  Create a static page
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSEditor();

$db     = getCMSDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $title       = trim($_POST['title']   ?? '');
        $slug        = cmsSlugify(trim($_POST['slug'] ?? $title));
        $content     = trim($_POST['content'] ?? '');
        $status      = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
        $showInMenu  = isset($_POST['show_in_menu']) ? 1 : 0;

        if ($title === '') {
            $errors[] = 'Title is required.';
        }
        if ($slug === '') {
            $errors[] = 'Slug is required.';
        }

        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM pages WHERE slug = :slug");
            $stmt->execute([':slug' => $slug]);
            if ($stmt->fetch()) {
                $errors[] = 'A page with this slug already exists.';
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO pages (title, slug, content, status, show_in_menu)
                     VALUES (:title, :slug, :content, :status, :show_in_menu)"
                );
                $stmt->execute([
                    ':title'        => $title,
                    ':slug'         => $slug,
                    ':content'      => $content,
                    ':status'       => $status,
                    ':show_in_menu' => $showInMenu,
                ]);
                cmsFlashMessage('Page created.', 'success');
                redirect(SITE_URL . '/admin/pages.php');
            }
        }
    }
}

$pageTitle = 'Create Page';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<div class="page-header">
    <h1>Create Page</h1>
    <a href="<?= SITE_URL ?>/admin/pages.php" class="btn btn--secondary">&larr; Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error">
        <ul class="error-list">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= cmsCsrfToken() ?>">

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title"
                   value="<?= e($_POST['title'] ?? '') ?>" maxlength="255" required autofocus>
        </div>

        <div class="form-group">
            <label for="slug">Slug <span class="hint">(auto-generated from title)</span></label>
            <input type="text" id="slug" name="slug"
                   value="<?= e($_POST['slug'] ?? '') ?>" maxlength="255">
        </div>

        <div class="form-group">
            <label for="content">Content <span class="hint">(HTML allowed)</span></label>
            <textarea id="content" name="content" rows="15"><?= e($_POST['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="draft"     <?= (($_POST['status'] ?? 'draft') === 'draft')     ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= (($_POST['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="show_in_menu" value="1"
                       <?= (($_POST['show_in_menu'] ?? '0') === '1') ? 'checked' : '' ?>>
                Show in navigation menu
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Create Page</button>
        </div>
    </form>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
