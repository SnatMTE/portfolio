<?php
/**
 * admin/index.php
 *
 * Admin dashboard. Shows at-a-glance statistics and recent orders.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$db    = getDB();
$admin = currentAdminUser();

// ---------------------------------------------------------------------------
// Statistics
// ---------------------------------------------------------------------------
$statsProducts = (int) $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$statsOrders   = (int) $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$statsPaid     = (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn();
$statsRevenue  = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'paid'")->fetchColumn();

// Five most recent orders
$recentOrders = $db->query("
    SELECT id, customer_name, customer_email, total, status, created_at
    FROM   orders
    ORDER  BY created_at DESC
    LIMIT  5
")->fetchAll();

$currentAdminPage = 'dashboard';

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <span>Welcome back, <strong><?= e($admin['username'] ?? 'Admin') ?></strong></span>
        </div>

        <?php renderFlash(); ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-card__label">Active Products</span>
                <span class="stat-card__value"><?= $statsProducts ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-card__label">Total Orders</span>
                <span class="stat-card__value"><?= $statsOrders ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-card__label">Paid Orders</span>
                <span class="stat-card__value"><?= $statsPaid ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-card__label">Total Revenue</span>
                <span class="stat-card__value"><?= formatPrice($statsRevenue) ?></span>
            </div>
        </div>

        <!-- Recent orders -->
        <div class="admin-section">
            <div class="admin-section__header">
                <h2>Recent Orders</h2>
                <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn--sm btn--outline">View all</a>
            </div>

            <?php if (count($recentOrders) > 0): ?>
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
                        <?php foreach ($recentOrders as $o): ?>
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
                            <td><a href="<?= SITE_URL ?>/admin/orders.php?id=<?= (int) $o['id'] ?>" class="btn btn--sm btn--outline">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p class="admin-empty">No orders yet.</p>
            <?php endif; ?>
        </div>

        <!-- Quick actions -->
        <div class="admin-section">
            <h2>Quick Actions</h2>
            <div class="quick-actions">
                <a href="<?= SITE_URL ?>/admin/create_product.php" class="btn btn--primary">+ Add Product</a>
                <a href="<?= SITE_URL ?>/admin/products.php"       class="btn btn--outline">Manage Products</a>
                <a href="<?= SITE_URL ?>/admin/orders.php"         class="btn btn--outline">View All Orders</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
