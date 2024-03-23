<?php
/**
 * admin/products.php
 *
 * Admin product listing page. Lists all products (active + inactive)
 * and supports inline delete.
 *
 * POST actions
 * ------------
 *   delete  – Deletes the product with the given product_id.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Handle delete
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        flashMessage('Invalid request.', 'error');
        redirect(SITE_URL . '/admin/products.php');
    }

    $action    = $_POST['action']     ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'delete' && $productId > 0) {
        $product = getProductById($productId);
        if ($product !== null) {
            // Remove image file if stored locally
            if (!empty($product['image'])) {
                $imagePath = ROOT_PATH . '/assets/images/' . basename($product['image']);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            deleteProduct($productId);
            flashMessage('Product deleted.', 'success');
        }
    }

    redirect(SITE_URL . '/admin/products.php');
}

// ---------------------------------------------------------------------------
// Load products
// ---------------------------------------------------------------------------
$products         = getAllProductsAdmin();
$currentAdminPage = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Products</h1>
            <a href="<?= SITE_URL ?>/admin/create_product.php" class="btn btn--primary">+ Add Product</a>
        </div>

        <?php renderFlash(); ?>

        <?php if (count($products) > 0): ?>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= (int) $p['id'] ?></td>
                        <td>
                            <a href="<?= SITE_URL ?>/product.php?id=<?= (int) $p['id'] ?>" target="_blank" rel="noopener">
                                <?= e($p['name']) ?>
                            </a>
                        </td>
                        <td><?= e($p['category_name'] ?? '—') ?></td>
                        <td><?= formatPrice((float) $p['price']) ?></td>
                        <td><?= (int) $p['stock'] ?></td>
                        <td>
                            <span class="badge badge--<?= $p['status'] === 'active' ? 'in' : 'out' ?>">
                                <?= e($p['status']) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="<?= SITE_URL ?>/admin/edit_product.php?id=<?= (int) $p['id'] ?>" class="btn btn--sm btn--outline">Edit</a>

                            <form method="post" action="<?= SITE_URL ?>/admin/products.php" class="inline-form"
                                  onsubmit="return confirm('Delete &quot;<?= e(addslashes($p['name'])) ?>&quot;? This cannot be undone.')">
                                <input type="hidden" name="csrf_token"  value="<?= csrfToken() ?>">
                                <input type="hidden" name="action"      value="delete">
                                <input type="hidden" name="product_id"  value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="btn btn--sm btn--danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="admin-empty">
                <p>No products yet. <a href="<?= SITE_URL ?>/admin/create_product.php">Add your first product</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
