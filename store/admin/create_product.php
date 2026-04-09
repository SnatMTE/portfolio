<?php
/**
 * admin/create_product.php
 *
 * Admin page for adding a new product.
 *
 * Handles image upload (stores file in assets/images/),
 * validates all fields, and inserts the product into the database.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$errors     = [];
$name       = '';
$shortDesc  = '';
$description = '';
$price      = '';
$stock      = '0';
$status     = 'active';
$categoryId = '';
$imageName  = '';

$categories = getAllCategories();

// ---------------------------------------------------------------------------
// Handle POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $name        = trim($_POST['name']        ?? '');
        $shortDesc   = trim($_POST['short_desc']  ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = trim($_POST['price']       ?? '');
        $stock       = trim($_POST['stock']       ?? '0');
        $status      = in_array($_POST['status'] ?? '', ['active', 'inactive'], true)
                        ? $_POST['status']
                        : 'active';
        $categoryId  = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;

        // ---- Validation ----
        if ($name === '') $errors[] = 'Product name is required.';
        elseif (mb_strlen($name) > 200) $errors[] = 'Name must be 200 characters or fewer.';

        if ($price === '' || !is_numeric($price) || (float) $price < 0) {
            $errors[] = 'A valid price is required (0 or more).';
        }
        if (!ctype_digit($stock) && !(preg_match('/^-?\d+$/', $stock))) {
            $errors[] = 'Stock must be a whole number.';
        }

        // ---- Image upload ----
        if (!empty($_FILES['image']['name'])) {
            $file      = $_FILES['image'];
            $allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxSize   = 2 * 1024 * 1024; // 2 MB

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload failed (error code ' . $file['error'] . ').';
            } elseif (!in_array($file['type'], $allowed, true)) {
                $errors[] = 'Image must be a JPEG, PNG, WebP, or GIF.';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Image must be 2 MB or smaller.';
            } else {
                $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $imageName = bin2hex(random_bytes(8)) . '.' . $ext;
                $destPath  = ROOT_PATH . '/assets/images/' . $imageName;

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    $errors[] = 'Could not save the uploaded image.';
                    $imageName = '';
                }
            }
        }

        // ---- Insert ----
        if (count($errors) === 0) {
            $slug = uniqueProductSlug($name);

            $productId = createProduct([
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'short_desc'  => $shortDesc,
                'price'       => round((float) $price, 2),
                'stock'       => (int) $stock,
                'image'       => $imageName !== '' ? $imageName : null,
                'category_id' => $categoryId,
                'status'      => $status,
            ]);

            flashMessage('Product "' . $name . '" created successfully.', 'success');
            redirect(SITE_URL . '/admin/products.php');
        }
    }
}

$currentAdminPage = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Add Product</h1>
            <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn--outline">&larr; Back to Products</a>
        </div>

        <?php if (count($errors) > 0): ?>
            <div class="alert alert--error" role="alert">
                <ul class="error-list">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/admin/create_product.php"
              enctype="multipart/form-data" class="admin-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-row">
                <div class="form-group form-group--grow">
                    <label for="name">Product Name <span aria-hidden="true">*</span></label>
                    <input id="name" type="text" name="name" class="form-control"
                           value="<?= e($name) ?>" required maxlength="200">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="short_desc">Short Description</label>
                <input id="short_desc" type="text" name="short_desc" class="form-control"
                       value="<?= e($shortDesc) ?>" maxlength="255"
                       placeholder="One-line summary shown on the product grid">
            </div>

            <div class="form-group">
                <label for="description">Full Description</label>
                <textarea id="description" name="description" class="form-control form-control--textarea"
                          rows="8" placeholder="Full product description…"><?= e($description) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price (<?= CURRENCY ?>) <span aria-hidden="true">*</span></label>
                    <input id="price" type="number" name="price" class="form-control"
                           value="<?= e($price) ?>" min="0" step="0.01" required placeholder="9.99">
                </div>

                <div class="form-group">
                    <label for="stock">Stock Quantity <span aria-hidden="true">*</span></label>
                    <input id="stock" type="number" name="stock" class="form-control"
                           value="<?= e($stock) ?>" min="0" step="1" required placeholder="10">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">— None —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= (string) ($categoryId ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Product Image (JPEG, PNG, WebP, GIF – max 2 MB)</label>
                <input id="image" type="file" name="image" class="form-control"
                       accept="image/jpeg,image/png,image/webp,image/gif">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Create Product</button>
                <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn--outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
