<?php
/**
 * admin/categories.php
 *
 * Manage blog categories.
 *
 * Handles three actions via GET ?action= parameter:
 *   - (default) / list  → Displays all categories in a table with add form.
 *   - delete            → Removes a category by ID (GET with CSRF token).
 *
 * Adding a category is handled via POST to the same page.
 * Category names are unique; slug is auto-generated from the name.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$errors = [];

// ---------------------------------------------------------------------------
// Delete category
// ---------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $delId = (int) ($_GET['id'] ?? 0);
    $csrf  = trim($_GET['csrf'] ?? '');

    if ($delId > 0 && validateCsrf($csrf)) {
        $stmt = getDB()->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        flashMessage('Category deleted.', 'success');
    } else {
        flashMessage('Invalid request.', 'error');
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// ---------------------------------------------------------------------------
// Add category
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = slugify(trim($_POST['slug'] ?? $name));

        if ($name === '') {
            $errors[] = 'Category name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Name must not exceed 100 characters.';
        }

        if (empty($errors)) {
            // Check uniqueness
            $check = getDB()->prepare("SELECT id FROM categories WHERE name = :name OR slug = :slug");
            $check->execute([':name' => $name, ':slug' => $slug]);
            if ($check->fetch()) {
                $errors[] = 'A category with that name or slug already exists.';
            }
        }

        if (empty($errors)) {
            $stmt = getDB()->prepare("INSERT INTO categories (name, slug) VALUES (:name, :slug)");
            $stmt->execute([':name' => $name, ':slug' => $slug]);
            flashMessage('Category "' . $name . '" created.', 'success');
            redirect(SITE_URL . '/admin/categories.php');
        }
    }
}

$allCategories    = getAllCategories();
$currentAdminPage = 'categories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Categories</h1>
        </div>

        <?php renderFlash(); ?>

        <!-- Add category form -->
        <div class="admin-form-card" style="max-width:520px;margin-bottom:2rem;">
            <h2 style="font-size:1rem;margin-bottom:1rem;">Add New Category</h2>

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
                        <label for="cat-name">Name</label>
                        <input id="cat-name" type="text" name="name" class="form-control"
                               maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="cat-slug">Slug <span style="color:var(--clr-text-muted);font-weight:400;">(optional)</span></label>
                        <input id="cat-slug" type="text" name="slug" class="form-control"
                               maxlength="120" placeholder="auto-generated" value="<?= e($_POST['slug'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--sm">Add Category</button>
            </form>
        </div>

        <!-- Categories table -->
        <?php if (empty($allCategories)): ?>
            <p style="color:var(--clr-text-muted);">No categories yet.</p>
        <?php else: ?>
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
                    <?php foreach ($allCategories as $cat): ?>
                        <tr>
                            <td><?= e($cat['name']) ?></td>
                            <td><code><?= e($cat['slug']) ?></code></td>
                            <td><?= formatDate($cat['created_at']) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/categories.php?action=delete&id=<?= (int)$cat['id'] ?>&csrf=<?= csrfToken() ?>"
                                   class="btn btn--sm btn--danger"
                                   data-confirm="Delete category &quot;<?= e(addslashes($cat['name'])) ?>&quot;? Posts in this category will be uncategorised.">
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
