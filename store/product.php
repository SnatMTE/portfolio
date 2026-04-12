<?php
/**
 * product.php
 *
 * Single product detail page.
 *
 * Query parameters
 * ----------------
 *   id  – Product ID (integer, required)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------------------------
// Load product
// ---------------------------------------------------------------------------
$id      = (int) ($_GET['id'] ?? 0);
$product = $id > 0 ? getProductById($id) : null;

if ($product === null || $product['status'] !== 'active') {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    $metaDesc  = 'The requested product could not be found.';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="empty-state"><h1>Product not found</h1><p>That product does not exist or is no longer available.</p>';
    echo '<a href="' . SITE_URL . '" class="btn btn--primary">Back to shop</a></div>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Handle "Add to cart" POST from this page
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        flashMessage('Invalid request. Please try again.', 'error');
        redirect(SITE_URL . '/product.php?id=' . $id);
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        if (addToCart($id, $qty)) {
            flashMessage($product['name'] . ' added to your cart.', 'success');
        } else {
            flashMessage('Could not add this product to your cart.', 'error');
        }
        redirect(SITE_URL . '/product.php?id=' . $id);
    }
}

// ---------------------------------------------------------------------------
// Page meta
// ---------------------------------------------------------------------------
$pageTitle = $product['name'];
$metaDesc  = $product['short_desc'] !== '' ? $product['short_desc'] : makeExcerpt($product['description'], 160);
$inStock   = (int) $product['stock'] > 0;

require_once __DIR__ . '/templates/header.php';
?>

<?php renderFlash(); ?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
        <li><a href="<?= SITE_URL ?>">Shop</a></li>
        <?php if (!empty($product['category_name'])): ?>
            <li><a href="<?= SITE_URL ?>?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a></li>
        <?php endif; ?>
        <li aria-current="page"><?= e($product['name']) ?></li>
    </ol>
</nav>

<!-- Product detail -->
<div class="product-detail">
    <!-- Image -->
    <div class="product-detail__gallery">
        <?php
            $imgFile = 'placeholder.svg';
            if (!empty($product['image'])) {
                $candidate = ROOT_PATH . '/assets/images/' . basename($product['image']);
                if (file_exists($candidate)) {
                    $imgFile = basename($product['image']);
                }
            }
            $imgUrl = SITE_URL . '/assets/images/' . $imgFile;
        ?>
        <img
            src="<?= e($imgUrl) ?>"
            alt="<?= e($product['name']) ?>"
            class="product-detail__main-image"
            width="600"
            height="450"
        >
    </div>

    <!-- Info -->
    <div class="product-detail__info">
        <?php if (!empty($product['category_name'])): ?>
            <a href="<?= SITE_URL ?>?category=<?= e($product['category_slug']) ?>" class="product-detail__category">
                <?= e($product['category_name']) ?>
            </a>
        <?php endif; ?>

        <h1 class="product-detail__title"><?= e($product['name']) ?></h1>

        <p class="product-detail__price"><?= formatPrice((float) $product['price']) ?></p>

        <?php if ($inStock): ?>
            <p class="product-detail__stock badge badge--in">In stock (<?= (int) $product['stock'] ?> available)</p>
        <?php else: ?>
            <p class="product-detail__stock badge badge--out">Out of stock</p>
        <?php endif; ?>

        <?php if (!empty($product['short_desc'])): ?>
            <p class="product-detail__short-desc"><?= e($product['short_desc']) ?></p>
        <?php endif; ?>

        <!-- Add to cart form -->
        <?php if ($inStock): ?>
        <form method="post" action="<?= SITE_URL ?>/product.php?id=<?= $id ?>" class="product-detail__form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action"     value="add">

            <div class="product-detail__qty-row">
                <label for="product-qty" class="sr-only">Quantity</label>
                <input
                    id="product-qty"
                    type="number"
                    name="qty"
                    value="1"
                    min="1"
                    max="<?= (int) $product['stock'] ?>"
                    class="qty-input"
                    aria-label="Quantity"
                >
                <button type="submit" class="btn btn--primary btn--lg">Add to cart</button>
            </div>
        </form>
        <?php endif; ?>

        <a href="<?= SITE_URL ?>/cart.php" class="product-detail__cart-link">View cart &rarr;</a>
    </div>
</div>

<!-- Full description -->
<?php if (!empty($product['description'])): ?>
<section class="product-description">
    <h2>Product Description</h2>
    <div class="prose">
        <?= nl2br(e($product['description'])) ?>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
