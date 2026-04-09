<?php
/**
 * cart.php
 *
 * Shopping cart page.
 * Handles add, update quantity, and remove actions via POST,
 * then displays the current cart contents with a subtotal.
 *
 * POST actions
 * ------------
 *   add     – Add product_id with qty to cart.
 *   update  – Set new qty for product_id (0 = remove).
 *   remove  – Remove product_id from cart.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------------------------
// Handle POST actions
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        flashMessage('Invalid request. Please try again.', 'error');
        redirect(SITE_URL . '/cart.php');
    }

    $action    = $_POST['action']     ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty       = (int) ($_POST['qty']        ?? 1);

    if ($productId > 0) {
        switch ($action) {
            case 'add':
                if (!addToCart($productId, max(1, $qty))) {
                    flashMessage('Could not add that product to your cart.', 'error');
                }
                break;

            case 'update':
                updateCartItem($productId, $qty);
                flashMessage('Cart updated.', 'success');
                break;

            case 'remove':
                removeFromCart($productId);
                flashMessage('Item removed from cart.', 'success');
                break;
        }
    }

    redirect(SITE_URL . '/cart.php');
}

// ---------------------------------------------------------------------------
// Render cart
// ---------------------------------------------------------------------------
$items    = getCartItems();
$total    = getCartTotal();
$pageTitle = 'Your Cart';
$metaDesc  = 'Review your cart before checkout.';

require_once __DIR__ . '/templates/header.php';
?>

<?php renderFlash(); ?>

<h1 class="page-title">Your Cart</h1>

<?php if (count($items) > 0): ?>

<div class="cart-layout">
    <!-- Cart table -->
    <div class="cart-table-wrapper">
        <table class="cart-table">
            <thead>
                <tr>
                    <th scope="col" class="sr-only">Image</th>
                    <th scope="col">Product</th>
                    <th scope="col">Price</th>
                    <th scope="col">Qty</th>
                    <th scope="col">Total</th>
                    <th scope="col" class="sr-only">Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php require __DIR__ . '/templates/cart_item.php'; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Order summary sidebar -->
    <aside class="cart-summary">
        <h2>Order Summary</h2>

        <dl class="summary-list">
            <?php foreach ($items as $item): ?>
                <div class="summary-list__row">
                    <dt><?= e($item['name']) ?> &times; <?= (int) $item['qty'] ?></dt>
                    <dd><?= formatPrice((float) $item['line_total']) ?></dd>
                </div>
            <?php endforeach; ?>
            <div class="summary-list__row summary-list__row--total">
                <dt>Total</dt>
                <dd><?= formatPrice($total) ?></dd>
            </div>
        </dl>

        <a href="<?= SITE_URL ?>/checkout.php" class="btn btn--primary btn--block">
            Proceed to Checkout
        </a>
        <a href="<?= SITE_URL ?>" class="btn btn--outline btn--block">
            Continue Shopping
        </a>
    </aside>
</div>

<?php else: ?>

<div class="empty-state">
    <p>Your cart is empty.</p>
    <a href="<?= SITE_URL ?>" class="btn btn--primary">Start Shopping</a>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
