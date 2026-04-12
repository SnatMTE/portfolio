<?php
/**
 * templates/admin_nav.php
 *
 * Sidebar navigation rendered on every admin panel page.
 *
 * Expects:
 *   $currentAdminPage  (string)  – Key identifying the active nav item.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

$currentAdminPage ??= '';

$navItems = [
    'dashboard' => ['label' => 'Dashboard',    'href' => '/admin/'],
    'events'    => ['label' => 'All Events',    'href' => '/admin/events.php'],
    'tokens'    => ['label' => 'Sync Tokens',   'href' => '/admin/tokens.php'],
    'import'    => ['label' => 'Import .ics',   'href' => '/import.php'],
    'export'    => ['label' => 'Export .ics',   'href' => '/export.php'],
];
?>
<nav class="admin-nav" aria-label="Admin navigation">
    <div class="admin-nav__brand">
        <a href="<?= SITE_URL ?>/admin/">&#128197; Calendar Admin</a>
    </div>

    <ul class="admin-nav__list">
        <?php foreach ($navItems as $key => $item): ?>
            <li class="admin-nav__item <?= $currentAdminPage === $key ? 'is-active' : '' ?>">
                <a href="<?= SITE_URL . $item['href'] ?>"><?= e($item['label']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="admin-nav__footer">
        <a href="<?= SITE_URL ?>" class="admin-nav__view-site">&#8617; View Calendar</a>
        <?php if (defined('CMS_URL')): ?>
            <a href="<?= CMS_URL ?>/admin/" class="admin-nav__view-site">&#8617; CMS Admin</a>
            <a href="<?= CMS_URL ?>/logout.php" class="admin-nav__logout">Log out</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/logout.php" class="admin-nav__logout">Log out</a>
        <?php endif; ?>
    </div>
</nav>
