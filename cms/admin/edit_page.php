<?php
/**
 * cms/admin/edit_page.php  —  Edit a static page
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSEditor();

$db     = getCMSDB();
$errors = [];
$pageId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM pages WHERE id = :id");
$stmt->execute([':id' => $pageId]);
$editPage = $stmt->fetch();

if (!$editPage) {
    cmsFlashMessage('Page not found.', 'error');
    redirect(SITE_URL . '/admin/pages.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $title      = trim($_POST['title']   ?? '');
        $slug       = cmsSlugify(trim($_POST['slug'] ?? ''));
        $content    = trim($_POST['content'] ?? '');
        $status     = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
        $showInMenu = isset($_POST['show_in_menu']) ? 1 : 0;

        if ($title === '') $errors[] = 'Title is required.';
        if ($slug  === '') $errors[] = 'Slug is required.';

        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM pages WHERE slug = :slug AND id != :id");
            $stmt->execute([':slug' => $slug, ':id' => $pageId]);
            if ($stmt->fetch()) {
                $errors[] = 'Another page already uses this slug.';
            } else {
                $stmt = $db->prepare(
                    "UPDATE pages SET title = :title, slug = :slug, content = :content,
                     status = :status, show_in_menu = :show_in_menu, updated_at = datetime('now') WHERE id = :id"
                );
                $stmt->execute([
                    ':title'        => $title,
                    ':slug'         => $slug,
                    ':content'      => $content,
                    ':status'       => $status,
                    ':show_in_menu' => $showInMenu,
                    ':id'           => $pageId,
                ]);
                cmsFlashMessage('Page updated.', 'success');
                redirect(SITE_URL . '/admin/pages.php');
            }
        }
    }
}

$formTitle      = $_POST['title']   ?? $editPage['title'];
$formSlug       = $_POST['slug']    ?? $editPage['slug'];
$formContent    = $_POST['content'] ?? $editPage['content'];
$formStatus     = $_POST['status']  ?? $editPage['status'];
$formShowInMenu = ($_SERVER['REQUEST_METHOD'] === 'POST')
    ? (isset($_POST['show_in_menu']) ? 1 : 0)
    : (int) $editPage['show_in_menu'];

$pageTitle = 'Edit Page';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<div class="page-header">
    <h1>Edit Page: <?= e($editPage['title']) ?></h1>
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
                   value="<?= e($formTitle) ?>" maxlength="255" required autofocus>
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
             <input type="text" id="slug" name="slug"
                 value="<?= e($formSlug) ?>" maxlength="255">
             <div id="slug-status" class="hint" aria-live="polite" style="margin-top:.35rem"></div>
        </div>

        <div class="form-group">
            <label for="content">Content <span class="hint">(HTML allowed)</span></label>
            <textarea id="content" name="content" rows="15"><?= e($formContent) ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="draft"     <?= ($formStatus === 'draft')     ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= ($formStatus === 'published') ? 'selected' : '' ?>>Published</option>
            </select>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="show_in_menu" value="1"
                       <?= $formShowInMenu ? 'checked' : '' ?>>
                Show in navigation menu
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Changes</button>
        </div>
    </form>
</div>

<script>
// Pretty URL slug helper + availability checker (edit mode)
(function(){
    const slugInput = document.getElementById('slug');
    const titleInput = document.getElementById('title');
    const statusEl = document.getElementById('slug-status');
    const checkUrl = '<?= SITE_URL ?>/admin/check_slug.php';
    const excludeId = <?= (int) $editPage['id'] ?>;

    function slugifyJS(s){
        return s.toString().toLowerCase().trim()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/[\s\-]+/g, '-')
            .replace(/^\-+|\-+$/g, '');
    }

    let timer = null;
    function check(slug){
        if (!slug) { statusEl.textContent = ''; return; }
        statusEl.textContent = 'Checking...';
        fetch(checkUrl + '?slug=' + encodeURIComponent(slug) + '&exclude_id=' + excludeId, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data) { statusEl.textContent = ''; return; }
                if (data.slug !== slug) {
                    statusEl.innerHTML = 'Suggested: <strong>' + data.slug + '</strong>' + (data.available ? ' — available' : ' — taken');
                    statusEl.style.color = data.available ? '' : 'var(--color-danger)';
                } else {
                    statusEl.textContent = data.available ? 'Available' : 'Already taken';
                    statusEl.style.color = data.available ? '' : 'var(--color-danger)';
                }
            })
            .catch(() => { statusEl.textContent = ''; });
    }

    titleInput.addEventListener('input', function(){
        const auto = slugifyJS(this.value);
        if (!slugInput.value || slugInput.value === '<?= e($editPage['slug']) ?>') {
            slugInput.value = auto;
            clearTimeout(timer);
            timer = setTimeout(()=>check(auto), 400);
        }
    });

    slugInput.addEventListener('input', function(){
        const s = slugifyJS(this.value);
        this.value = s;
        clearTimeout(timer);
        timer = setTimeout(()=>check(s), 400);
    });
})();
</script>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
