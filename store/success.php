<?php
/**
 * success.php
 *
 * PayPal payment return handler (success path).
 *
 * PayPal returns the customer here after approval with the query params:
 *   token          – The PayPal Order ID
 *   PayerID        – The PayPal payer ID
 *
 * This page:
 *  1. Verifies the PayPal Order ID matches the one stored in the session.
 *  2. Calls the PayPal Capture API server-side to finalise the payment.
 *  3. Confirms the capture status is COMPLETED (defence-in-depth).
 *  4. Marks the local order as 'paid' and stores the transaction ID.
 *  5. Clears the cart and session payment keys.
 *  6. Displays a confirmation to the customer.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/payments/paypal.php';

// ---------------------------------------------------------------------------
// Validate session state
// ---------------------------------------------------------------------------
$pendingOrderId = (int) ($_SESSION['pending_order_id'] ?? 0);
$sessionPaypalId = $_SESSION['paypal_order_id'] ?? '';

if ($pendingOrderId === 0 || $sessionPaypalId === '') {
    // No pending order in session – may be a direct visit or stale session
    flashMessage('No pending order found. Please start a new checkout.', 'error');
    redirect(SITE_URL . '/cart.php');
}

// ---------------------------------------------------------------------------
// Verify PayPal return parameters
// ---------------------------------------------------------------------------
$returnedPaypalId = trim($_GET['token'] ?? '');

if ($returnedPaypalId === '') {
    flashMessage('Missing payment token. Please try again.', 'error');
    redirect(SITE_URL . '/cart.php');
}

// Ensure the returned token matches the one we stored (prevents token swap)
if ($returnedPaypalId !== $sessionPaypalId) {
    error_log('PayPal token mismatch. Session: ' . $sessionPaypalId . ' Returned: ' . $returnedPaypalId);
    flashMessage('Payment verification failed. Please contact support.', 'error');
    redirect(SITE_URL . '/cart.php');
}

// ---------------------------------------------------------------------------
// Load local order and prevent double-processing
// ---------------------------------------------------------------------------
$order = getOrderById($pendingOrderId);

if ($order === null) {
    flashMessage('Order not found. Please contact support.', 'error');
    redirect(SITE_URL . '/');
}

if ($order['status'] === 'paid') {
    // Already processed (e.g. user refreshed the success page)
    $alreadyPaid = true;
} else {
    $alreadyPaid = false;

    // ---------------------------------------------------------------------------
    // Capture the PayPal order server-side
    // ---------------------------------------------------------------------------
    try {
        $captureData = paypalCaptureOrder($returnedPaypalId);

        if (paypalIsPaymentComplete($captureData)) {
            $transactionId = paypalGetTransactionId($captureData);
            $detail        = json_encode($captureData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            // Mark order as paid in the database
            updateOrderPayment($pendingOrderId, 'paid', $transactionId, $detail);

            // Clear cart and session payment keys
            clearCart();
            unset($_SESSION['pending_order_id'], $_SESSION['paypal_order_id']);

        } else {
            // Payment not completed (e.g. declined)
            $captureStatus = $captureData['status'] ?? 'UNKNOWN';
            error_log('PayPal capture not completed. Order: ' . $pendingOrderId . ' Status: ' . $captureStatus);
            updateOrderPayment($pendingOrderId, 'cancelled', '', json_encode($captureData));
            flashMessage('Payment was not completed (' . $captureStatus . '). You have not been charged.', 'error');
            redirect(SITE_URL . '/cancel.php');
        }

    } catch (Throwable $e) {
        error_log('PayPal capture error for order ' . $pendingOrderId . ': ' . $e->getMessage());
        flashMessage('There was a problem confirming your payment. Please contact support with order #' . $pendingOrderId . '.', 'error');
        // Do NOT clear the session – let them retry or contact support
        redirect(SITE_URL . '/cart.php');
    }

    // Reload order with updated status
    $order = getOrderById($pendingOrderId);
}

// ---------------------------------------------------------------------------
// Display success page
// ---------------------------------------------------------------------------
$pageTitle = 'Order Confirmed';
$metaDesc  = 'Your order has been placed successfully.';

require_once __DIR__ . '/templates/header.php';
?>

<div class="success-page">
    <div class="success-icon" aria-hidden="true">&#10003;</div>
    <h1 class="success-title">
        <?= $alreadyPaid ? 'Order Already Confirmed' : 'Thank You for Your Order!' ?>
    </h1>
    <p class="success-sub">
        A confirmation email will be sent to <strong><?= e($order['customer_email']) ?></strong>.
    </p>

    <div class="order-confirmation">
        <h2>Order #<?= (int) $order['id'] ?></h2>

        <dl class="order-meta">
            <div>
                <dt>Name</dt>
                <dd><?= e($order['customer_name']) ?></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd><?= e($order['customer_email']) ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><span class="badge badge--in">Paid</span></dd>
            </div>
            <div>
                <dt>Transaction ID</dt>
                <dd><?= e($order['payment_id'] ?? '—') ?></dd>
            </div>
            <div>
                <dt>Date</dt>
                <dd><?= formatDate($order['created_at']) ?></dd>
            </div>
        </dl>

        <!-- Order items -->
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Line total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $line): ?>
                    <tr>
                        <td><?= e($line['product_name']) ?></td>
                        <td><?= (int) $line['quantity'] ?></td>
                        <td><?= formatPrice((float) $line['price']) ?></td>
                        <td><?= formatPrice((float) $line['price'] * (int) $line['quantity']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong><?= formatPrice((float) $order['total']) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <a href="<?= SITE_URL ?>" class="btn btn--primary">Continue Shopping</a>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
