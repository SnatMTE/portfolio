<?php
/**
 * checkout.php
 *
 * Checkout page and order creation.
 *
 * Flow
 * ----
 *  1. GET  – Display the checkout form (customer name + email).
 *  2. POST – Validate input, create a pending order in the database,
 *            create a PayPal order via the API, and redirect to PayPal.
 *
 * The total is always recalculated server-side from database prices.
 * Client-submitted totals are never trusted.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/payments/paypal.php';

// Redirect to shop if cart is empty
$items = getCartItems();
if (count($items) === 0) {
    flashMessage('Your cart is empty.', 'info');
    redirect(SITE_URL . '/cart.php');
}

$errors     = [];
$name       = '';
$email      = '';

// ---------------------------------------------------------------------------
// Process POST – validate and initiate payment
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $name  = trim($_POST['customer_name']  ?? '');
        $email = trim($_POST['customer_email'] ?? '');

        // Validate name
        if ($name === '') {
            $errors[] = 'Please enter your name.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Name must be 100 characters or fewer.';
        }

        // Validate email
        if ($email === '') {
            $errors[] = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (count($errors) === 0) {
            // Re-fetch cart items so prices are always from the DB
            $items = getCartItems();
            if (count($items) === 0) {
                redirect(SITE_URL . '/cart.php');
            }

            $total = getCartTotal();

            try {
                // 1. Create a pending order in the database
                $orderId = createOrder($name, $email, $items);

                // 2. Store order ID in session for verification on return
                $_SESSION['pending_order_id'] = $orderId;

                // 3. Build PayPal return / cancel URLs
                $returnUrl = SITE_URL . '/success.php';
                $cancelUrl = SITE_URL . '/cancel.php';

                // 4. Create the PayPal order
                $paypal = paypalCreateOrder($items, $total, $returnUrl, $cancelUrl);

                // 5. Store the PayPal order ID in session to verify on return
                $_SESSION['paypal_order_id'] = $paypal['paypal_order_id'];

                // 6. Redirect to PayPal checkout
                redirect($paypal['approve_url']);

            } catch (Throwable $e) {
                // Log the real error internally; show a safe message to the user
                error_log('Checkout error: ' . $e->getMessage());
                $errors[] = 'An error occurred while processing your order. Please try again.';
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Render checkout form
// ---------------------------------------------------------------------------
$total     = getCartTotal();
$pageTitle = 'Checkout';
$metaDesc  = 'Complete your purchase.';

require_once __DIR__ . '/templates/header.php';
?>

<?php renderFlash(); ?>

<h1 class="page-title">Checkout</h1>

<div class="checkout-layout">

    <!-- Customer details form -->
    <div class="checkout-form-wrapper">
        <h2>Your Details</h2>

        <?php if (count($errors) > 0): ?>
            <div class="alert alert--error" role="alert">
                <ul class="error-list">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/checkout.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="customer_name">Full Name <span aria-hidden="true">*</span></label>
                <input
                    id="customer_name"
                    type="text"
                    name="customer_name"
                    class="form-control"
                    value="<?= e($name) ?>"
                    placeholder="Jane Smith"
                    required
                    maxlength="100"
                    autocomplete="name"
                >
            </div>

            <div class="form-group">
                <label for="customer_email">Email Address <span aria-hidden="true">*</span></label>
                <input
                    id="customer_email"
                    type="email"
                    name="customer_email"
                    class="form-control"
                    value="<?= e($email) ?>"
                    placeholder="you@example.com"
                    required
                    maxlength="254"
                    autocomplete="email"
                >
            </div>

            <div class="checkout-paypal-info">
                <p>You will be redirected to PayPal to complete your payment securely.</p>
            </div>

            <button type="submit" class="btn btn--primary btn--lg btn--block">
                Pay <?= formatPrice($total) ?> with PayPal
            </button>
        </form>
    </div>

    <!-- Order summary -->
    <aside class="checkout-summary">
        <h2>Order Summary</h2>

        <ul class="checkout-items">
            <?php foreach ($items as $item): ?>
                <li class="checkout-item">
                    <span class="checkout-item__name">
                        <?= e($item['name']) ?> &times; <?= (int) $item['qty'] ?>
                    </span>
                    <span class="checkout-item__price"><?= formatPrice((float) $item['line_total']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="checkout-total">
            <span>Total</span>
            <strong><?= formatPrice($total) ?></strong>
        </div>

        <a href="<?= SITE_URL ?>/cart.php" class="btn btn--outline btn--sm">Edit cart</a>
    </aside>

</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
