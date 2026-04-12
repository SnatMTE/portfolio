<?php
/**
 * cms/admin/pages.php  —  Static page management
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSEditor();

$db    = getCMSDB();
$flash = cmsGetFlash();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        cmsFlashMessage('Invalid security token.', 'error');
    } else {
        $stmt = $db->prepare("DELETE FROM pages WHERE id = :id");
        $stmt->execute([':id' => (int) $_POST['delete_id']]);
        cmsFlashMessage('Page deleted.', 'success');
    }
    redirect(SITE_URL . '/admin/pages.php');
}

$pages = $db->query(
    "SELECT id, title, slug, status, created_at FROM pages ORDER BY created_at DESC"
)->fetchAll();

$pageTitle = 'Pages';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Pages</h1>
    <a href="<?= SITE_URL ?>/admin/create_page.php" class="btn btn--primary">+ New Page</a>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $pg): ?>
                <tr>
                    <td><?= e($pg['title']) ?></td>
                    <td><code><?= e($pg['slug']) ?></code></td>
                    <td><span class="badge badge--<?= e($pg['status']) ?>"><?= e($pg['status']) ?></span></td>
                    <td><?= e(cmsFormatDate($pg['created_at'])) ?></td>
                    <td class="actions">
                        <a href="<?= SITE_URL ?>/page/<?= e($pg['slug']) ?>" target="_blank" class="btn btn--sm">View</a>
                        <a href="<?= SITE_URL ?>/admin/edit_page.php?id=<?= (int) $pg['id'] ?>" class="btn btn--sm">Edit</a>
                        <form method="post" action="" style="display:inline"
                              onsubmit="return confirm('Delete this page?')">
                            <input type="hidden" name="csrf_token"  value="<?= cmsCsrfToken() ?>">
                            <input type="hidden" name="delete_id"   value="<?= (int) $pg['id'] ?>">
                            <button type="submit" class="btn btn--sm btn--danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pages)): ?>
                <tr><td colspan="5" class="empty">No pages yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
