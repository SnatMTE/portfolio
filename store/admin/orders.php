<?php
/**
 * admin/orders.php
 *
 * Admin order management page.
 * Lists all orders with their status and allows viewing individual order details.
 *
 * Query parameters
 * ----------------
 *   id  – If provided, shows detail view for that order ID.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Detail view
// ---------------------------------------------------------------------------
$detailId = (int) ($_GET['id'] ?? 0);
$detail   = null;

if ($detailId > 0) {
    $detail = getOrderById($detailId);
    if ($detail === null) {
        flashMessage('Order not found.', 'error');
        redirect(SITE_URL . '/admin/orders.php');
    }
}

// ---------------------------------------------------------------------------
// Handle status update (POST)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        flashMessage('Invalid request.', 'error');
        redirect(SITE_URL . '/admin/orders.php');
    }

    $orderId   = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['pending', 'paid', 'cancelled', 'refunded'];

    if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
        $stmt = getDB()->prepare("
            UPDATE orders SET status = :s, updated_at = datetime('now') WHERE id = :id
        ");
        $stmt->execute([':s' => $newStatus, ':id' => $orderId]);
        flashMessage('Order #' . $orderId . ' status updated to "' . $newStatus . '".', 'success');
    }

    redirect(SITE_URL . '/admin/orders.php' . ($detailId > 0 ? '?id=' . $detailId : ''));
}

// ---------------------------------------------------------------------------
// Order listing
// ---------------------------------------------------------------------------
$orders           = getAllOrders(200);
$currentAdminPage = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">

        <?php if ($detail !== null): ?>
        <!-- ================================================================
             ORDER DETAIL VIEW
             ================================================================ -->
        <div class="admin-header">
            <h1>Order #<?= (int) $detail['id'] ?></h1>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn--outline">&larr; All Orders</a>
        </div>

        <?php renderFlash(); ?>

        <div class="order-detail-layout">
            <!-- Order meta -->
            <div class="order-meta-card">
                <h2>Order Details</h2>
                <dl class="order-meta">
                    <div><dt>Customer</dt><dd><?= e($detail['customer_name']) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($detail['customer_email']) ?></dd></div>
                    <div><dt>Total</dt><dd><?= formatPrice((float) $detail['total']) ?></dd></div>
                    <div>
                        <dt>Status</dt>
                        <dd>
                            <span class="badge badge--<?= $detail['status'] === 'paid' ? 'in' : ($detail['status'] === 'pending' ? 'warning' : 'out') ?>">
                                <?= e($detail['status']) ?>
                            </span>
                        </dd>
                    </div>
                    <div><dt>Payment Provider</dt><dd><?= e($detail['payment_provider']) ?></dd></div>
                    <div><dt>Transaction ID</dt><dd><?= e($detail['payment_id'] ?? '—') ?></dd></div>
                    <div><dt>Date</dt><dd><?= formatDate($detail['created_at'], 'j F Y, H:i') ?></dd></div>
                </dl>

                <!-- Update status -->
                <form method="post" action="<?= SITE_URL ?>/admin/orders.php?id=<?= (int) $detail['id'] ?>" class="status-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="order_id"   value="<?= (int) $detail['id'] ?>">
                    <label for="new_status">Update Status</label>
                    <div class="form-row form-row--tight">
                        <select id="new_status" name="new_status" class="form-control">
                            <?php foreach (['pending', 'paid', 'cancelled', 'refunded'] as $s): ?>
                                <option value="<?= $s ?>" <?= $detail['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn--primary btn--sm">Update</button>
                    </div>
                </form>
            </div>

            <!-- Order items -->
            <div class="order-items-card">
                <h2>Items</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail['items'] as $line): ?>
                        <tr>
                            <td><?= e($line['product_name']) ?></td>
                            <td><?= formatPrice((float) $line['price']) ?></td>
                            <td><?= (int) $line['quantity'] ?></td>
                            <td><?= formatPrice((float) $line['price'] * (int) $line['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong><?= formatPrice((float) $detail['total']) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php else: ?>
        <!-- ================================================================
             ORDER LISTING
             ================================================================ -->
        <div class="admin-header">
            <h1>Orders</h1>
        </div>

        <?php renderFlash(); ?>

        <?php if (count($orders) > 0): ?>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= (int) $o['id'] ?></td>
                        <td><?= e($o['customer_name']) ?></td>
                        <td><?= e($o['customer_email']) ?></td>
                        <td><?= formatPrice((float) $o['total']) ?></td>
                        <td>
                            <span class="badge badge--<?= $o['status'] === 'paid' ? 'in' : ($o['status'] === 'pending' ? 'warning' : 'out') ?>">
                                <?= e($o['status']) ?>
                            </span>
                        </td>
                        <td><?= formatDate($o['created_at']) ?></td>
                        <td>
                            <a href="<?= SITE_URL ?>/admin/orders.php?id=<?= (int) $o['id'] ?>"
                               class="btn btn--sm btn--outline">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="admin-empty"><p>No orders yet.</p></div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
