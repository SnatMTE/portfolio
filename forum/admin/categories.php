<?php
/**
 * admin/categories.php
 *
 * Create, edit, reorder, and delete forum categories.
 *
 * Actions (POST):
 *   create  - Insert a new category
 *   edit    - Update an existing category
 *   delete  - Delete a category (cascades to threads and posts)
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireAdminAuth();

$db = getDB();

// ---------------------------------------------------------------------------
// Handle POST actions
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $order       = (int) ($_POST['display_order'] ?? 0);

        if ($name === '') {
            flashMessage('Category name cannot be empty.', 'error');
        } else {
            $slug = slugify($name);
            // Ensure unique slug
            $base = $slug;
            $n    = 2;
            $check = $db->prepare("SELECT id FROM categories WHERE slug = :slug");
            do {
                $check->execute([':slug' => $slug]);
                if (!$check->fetch()) break;
                $slug = $base . '-' . $n++;
            } while (true);

            $stmt = $db->prepare(
                "INSERT INTO categories (name, slug, description, display_order)
                 VALUES (:name, :slug, :desc, :order)"
            );
            $stmt->execute([
                ':name'  => $name,
                ':slug'  => $slug,
                ':desc'  => $description,
                ':order' => $order,
            ]);
            flashMessage('Category "' . $name . '" created.', 'success');
        }
    }

    if ($action === 'edit') {
        $id          = (int) ($_POST['id']           ?? 0);
        $name        = trim($_POST['name']            ?? '');
        $description = trim($_POST['description']     ?? '');
        $order       = (int) ($_POST['display_order'] ?? 0);

        if ($id <= 0 || $name === '') {
            flashMessage('Invalid data supplied.', 'error');
        } else {
            $stmt = $db->prepare(
                "UPDATE categories
                 SET name = :name, description = :desc, display_order = :order
                 WHERE id = :id"
            );
            $stmt->execute([
                ':name'  => $name,
                ':desc'  => $description,
                ':order' => $order,
                ':id'    => $id,
            ]);
            flashMessage('Category updated.', 'success');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => $id]);
            flashMessage('Category deleted.', 'success');
        }
    }

    redirect(SITE_URL . '/admin/categories.php');
}

// ---------------------------------------------------------------------------
// Editing an existing category?
// ---------------------------------------------------------------------------
$editCategory = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editCategory = getCategoryById((int) $_GET['id']);
}

$categories      = getCategories();
$pageTitle       = 'Manage Categories';
$activeAdminPage = 'categories';
require_once dirname(__DIR__) . '/templates/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Categories</h1>
        </div>

        <?php if ($editCategory): ?>
            <!-- Edit form -->
            <div class="admin-form-card" style="margin-bottom:2rem">
                <h2 class="admin-section-title">Edit Category</h2>
                <form method="post" action="<?= SITE_URL ?>/admin/categories.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action"     value="edit">
                    <input type="hidden" name="id"         value="<?= (int) $editCategory['id'] ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_name">Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control"
                                   value="<?= e($editCategory['name']) ?>" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="edit_order">Display Order</label>
                            <input type="number" name="display_order" id="edit_order" class="form-control"
                                   value="<?= (int) $editCategory['display_order'] ?>" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_desc">Description</label>
                        <input type="text" name="description" id="edit_desc" class="form-control"
                               value="<?= e($editCategory['description']) ?>" maxlength="255">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Save Changes</button>
                        <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn--outline">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Create form -->
            <div class="admin-form-card" style="margin-bottom:2rem">
                <h2 class="admin-section-title">New Category</h2>
                <form method="post" action="<?= SITE_URL ?>/admin/categories.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action"     value="create">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                   placeholder="e.g. General" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" id="display_order"
                                   class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description <small class="text-muted">(optional)</small></label>
                        <input type="text" name="description" id="description" class="form-control"
                               placeholder="Brief description shown on the homepage" maxlength="255">
                    </div>

                    <button type="submit" class="btn btn--primary">Create Category</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Category list -->
        <?php if (empty($categories)): ?>
            <p class="text-muted">No categories yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Threads</th>
                        <th>Posts</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/category.php?slug=<?= e($cat['slug']) ?>">
                                    <?= e($cat['name']) ?>
                                </a>
                                <?php if ($cat['description']): ?>
                                    <br><small class="text-muted"><?= e(truncate($cat['description'], 60)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($cat['slug']) ?></code></td>
                            <td><?= number_format((int) $cat['thread_count']) ?></td>
                            <td><?= number_format((int) $cat['post_count']) ?></td>
                            <td><?= (int) $cat['display_order'] ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/categories.php?action=edit&id=<?= (int) $cat['id'] ?>"
                                   class="btn btn--outline btn--sm">Edit</a>
                                <form method="post" action="<?= SITE_URL ?>/admin/categories.php"
                                      style="display:inline"
                                      onsubmit="return confirm('Delete category and ALL its threads/posts?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action"     value="delete">
                                    <input type="hidden" name="id"         value="<?= (int) $cat['id'] ?>">
                                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>
