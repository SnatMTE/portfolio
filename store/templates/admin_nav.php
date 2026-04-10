<?php
/**
 * templates/admin_nav.php
 *
 * Renders the admin panel's sidebar navigation using the shared
 * `admin-nav` markup used by other modules (blog/forum) so the
 * layout is consistent across admin areas.
 *
 * Expects $currentAdminPage (string) to be set by the including file.
 */

$currentAdminPage ??= '';

$navItems = [
    'dashboard'      => ['label' => 'Dashboard',     'href' => '/admin/'],
    'products'       => ['label' => 'Products',      'href' => '/admin/products.php'],
    'create_product' => ['label' => 'New Product',   'href' => '/admin/create_product.php'],
    'orders'         => ['label' => 'Orders',        'href' => '/admin/orders.php'],
    'settings'       => ['label' => 'Settings',      'href' => '/admin/settings.php'],
];
?>
<nav class="admin-nav" aria-label="Admin navigation">
    <div class="admin-nav__brand">
        <a href="<?= SITE_URL ?>/admin/">&#9881; <?= e(SITE_NAME) ?></a>
    </div>

    <ul class="admin-nav__list">
        <?php foreach ($navItems as $key => $item): ?>
            <li class="admin-nav__item <?= $currentAdminPage === $key ? 'is-active' : '' ?>">
                <a href="<?= SITE_URL . $item['href'] ?>"><?= e($item['label']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="admin-nav__footer">
        <a href="<?= SITE_URL ?>" class="admin-nav__view-site">&#8617; View Store</a>
        <a href="<?= SITE_URL ?>/logout.php" class="admin-nav__logout">Log out</a>
    </div>
</nav>
