<?php
/**
 * templates/admin_nav.php
 *
 * Renders the sticky admin sidebar navigation.
 * Expects $currentAdminPage (string) matching a nav item key, e.g. 'dashboard'.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

$currentAdminPage ??= '';

$navItems = [
    'dashboard'  => ['label' => 'Dashboard',   'href' => SITE_URL . '/admin/'],
    'categories' => ['label' => 'Categories',  'href' => SITE_URL . '/admin/categories.php'],
    'threads'    => ['label' => 'Threads',      'href' => SITE_URL . '/admin/threads.php'],
    'posts'      => ['label' => 'Posts',        'href' => SITE_URL . '/admin/posts.php'],
    'users'      => ['label' => 'Users',        'href' => SITE_URL . '/admin/users.php'],
];
?>
<nav class="admin-nav" aria-label="Admin navigation">
    <div class="admin-nav__brand">
        <a href="<?= SITE_URL ?>/admin/"><?= e(SITE_NAME) ?> Admin</a>
    </div>

    <ul class="admin-nav__list">
        <?php foreach ($navItems as $key => $item): ?>
            <li class="admin-nav__item<?= $currentAdminPage === $key ? ' is-active' : '' ?>">
                <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="admin-nav__footer">
        <a href="<?= SITE_URL ?>/" class="admin-nav__view-site">View forum</a>
        <a href="<?= SITE_URL ?>/logout.php" class="admin-nav__logout">Log out</a>
    </div>
</nav>
