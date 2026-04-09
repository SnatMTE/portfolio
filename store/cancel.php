<?php
/**
 * cancel.php
 *
 * PayPal payment cancellation handler.
 *
 * Reached when the customer clicks "Cancel" on PayPal's checkout page,
 * or when a payment fails to complete. Marks the local order as
 * 'cancelled' (if still pending) and informs the customer.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------------------------
// Cancel any pending order still in session
// ---------------------------------------------------------------------------
$pendingOrderId = (int) ($_SESSION['pending_order_id'] ?? 0);

if ($pendingOrderId > 0) {
    $order = getOrderById($pendingOrderId);
    if ($order !== null && $order['status'] === 'pending') {
        // Restore stock for each item since the order was not paid
        foreach ($order['items'] as $line) {
            if (!empty($line['product_id'])) {
                getDB()->prepare("
                    UPDATE products SET stock = stock + :qty WHERE id = :id
                ")->execute([':qty' => (int) $line['quantity'], ':id' => (int) $line['product_id']]);
            }
        }
        updateOrderPayment($pendingOrderId, 'cancelled', '', '');
    }

    unset($_SESSION['pending_order_id'], $_SESSION['paypal_order_id']);
}

// ---------------------------------------------------------------------------
// Render cancellation page
// ---------------------------------------------------------------------------
$pageTitle = 'Payment Cancelled';
$metaDesc  = 'Your payment was cancelled. Your cart has been preserved.';

require_once __DIR__ . '/templates/header.php';
?>

<div class="cancel-page">
    <div class="cancel-icon" aria-hidden="true">&#10007;</div>
    <h1>Payment Cancelled</h1>
    <p>Your payment was cancelled and you have not been charged.</p>
    <p>Your cart items have been preserved. You can try again at any time.</p>

    <div class="cancel-actions">
        <a href="<?= SITE_URL ?>/checkout.php" class="btn btn--primary">Try Again</a>
        <a href="<?= SITE_URL ?>/cart.php"    class="btn btn--outline">View Cart</a>
        <a href="<?= SITE_URL ?>"             class="btn btn--outline">Continue Shopping</a>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
