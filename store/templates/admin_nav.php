<?php
/**
 * templates/admin_nav.php
 *
 * Renders the left-hand sidebar navigation for every admin panel page.
 * Expects $currentAdminPage (string) to be set by the including file to
 * highlight the active menu item.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

$currentAdminPage ??= '';

$navItems = [
    'dashboard' => ['label' => 'Dashboard',    'href' => SITE_URL . '/admin/'],
    'products'  => ['label' => 'Products',     'href' => SITE_URL . '/admin/products.php'],
    'orders'    => ['label' => 'Orders',       'href' => SITE_URL . '/admin/orders.php'],
    'settings'  => ['label' => 'Settings',     'href' => SITE_URL . '/admin/settings.php'],
];
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar__logo">
        <a href="<?= SITE_URL ?>/admin/"><?= e(SITE_NAME) ?></a>
        <span>Admin</span>
    </div>

    <nav class="admin-sidebar__nav" aria-label="Admin navigation">
        <ul>
            <?php foreach ($navItems as $key => $item): ?>
                <li>
                    <a
                        href="<?= $item['href'] ?>"
                        class="admin-nav-link <?= $currentAdminPage === $key ? 'is-active' : '' ?>"
                    >
                        <?= e($item['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="admin-sidebar__footer">
        <a href="<?= SITE_URL ?>" class="admin-nav-link" target="_blank" rel="noopener">
            &larr; View Store
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="admin-nav-link admin-nav-link--logout">
            Log out
        </a>
    </div>
</aside>
