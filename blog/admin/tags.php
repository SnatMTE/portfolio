<?php
/**
 * admin/tags.php
 *
 * Manage blog tags.
 *
 * Handles three operations:
 *   - Default view  → Lists all tags with a create form.
 *   - POST          → Creates a new tag (name + auto-slug).
 *   - GET ?action=delete → Removes a tag by ID (CSRF-protected).
 *
 * Deleting a tag also removes its entries from the post_tags join table
 * (handled by ON DELETE CASCADE in the schema).
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$errors = [];

// ---------------------------------------------------------------------------
// Delete tag
// ---------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $delId = (int) ($_GET['id'] ?? 0);
    $csrf  = trim($_GET['csrf'] ?? '');

    if ($delId > 0 && validateCsrf($csrf)) {
        $stmt = getDB()->prepare("DELETE FROM tags WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        flashMessage('Tag deleted.', 'success');
    } else {
        flashMessage('Invalid request.', 'error');
    }
    redirect(SITE_URL . '/admin/tags.php');
}

// ---------------------------------------------------------------------------
// Add tag
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = slugify(trim($_POST['slug'] ?? $name));

        if ($name === '') {
            $errors[] = 'Tag name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Name must not exceed 100 characters.';
        }

        if (empty($errors)) {
            $check = getDB()->prepare("SELECT id FROM tags WHERE name = :name OR slug = :slug");
            $check->execute([':name' => $name, ':slug' => $slug]);
            if ($check->fetch()) {
                $errors[] = 'A tag with that name or slug already exists.';
            }
        }

        if (empty($errors)) {
            $stmt = getDB()->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
            $stmt->execute([':name' => $name, ':slug' => $slug]);
            flashMessage('Tag "' . $name . '" created.', 'success');
            redirect(SITE_URL . '/admin/tags.php');
        }
    }
}

$allTags          = getAllTags();
$currentAdminPage = 'tags';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Tags</h1>
        </div>

        <?php renderFlash(); ?>

        <!-- Add tag form -->
        <div class="admin-form-card" style="max-width:520px;margin-bottom:2rem;">
            <h2 style="font-size:1rem;margin-bottom:1rem;">Add New Tag</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert--error" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="tag-name">Name</label>
                        <input id="tag-name" type="text" name="name" class="form-control"
                               maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tag-slug">Slug <span style="color:var(--clr-text-muted);font-weight:400;">(optional)</span></label>
                        <input id="tag-slug" type="text" name="slug" class="form-control"
                               maxlength="120" placeholder="auto-generated" value="<?= e($_POST['slug'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--sm">Add Tag</button>
            </form>
        </div>

        <!-- Tags table -->
        <?php if (empty($allTags)): ?>
            <p style="color:var(--clr-text-muted);">No tags yet.</p>
        <?php else: ?>
            <div class="tag-cloud" style="margin-bottom:1.5rem;">
                <?php foreach ($allTags as $tag): ?>
                    <a href="<?= SITE_URL ?>/tag/<?= e($tag['slug']) ?>" class="tag-pill" target="_blank">
                        <?= e($tag['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allTags as $tag): ?>
                        <tr>
                            <td><?= e($tag['name']) ?></td>
                            <td><code><?= e($tag['slug']) ?></code></td>
                            <td><?= formatDate($tag['created_at']) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/tags.php?action=delete&id=<?= (int)$tag['id'] ?>&csrf=<?= csrfToken() ?>"
                                   class="btn btn--sm btn--danger"
                                   data-confirm="Delete tag &quot;<?= e(addslashes($tag['name'])) ?>&quot;?">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
