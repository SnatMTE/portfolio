<?php
/**
 * templates/admin_nav.php
 *
 * Renders the admin panel sidebar navigation.
 * Included at the top of every admin page after auth.php verifies the session.
 *
 * Expects:
 *   $currentAdminPage  (string)  – Key used to highlight the active nav item.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

$currentAdminPage ??= '';

$navItems = [
    'dashboard' => ['label' => 'Dashboard',    'href' => '/admin/'],
    'files'     => ['label' => 'All Files',    'href' => '/admin/files.php'],
    'upload'    => ['label' => 'Upload File',  'href' => '/upload.php'],
];
?>
<nav class="admin-nav" aria-label="Admin navigation">
    <div class="admin-nav__brand">
        <a href="<?= SITE_URL ?>/admin/">&#128190; DL Manager</a>
    </div>

    <ul class="admin-nav__list">
        <?php foreach ($navItems as $key => $item): ?>
            <li class="admin-nav__item <?= $currentAdminPage === $key ? 'is-active' : '' ?>">
                <a href="<?= SITE_URL . $item['href'] ?>"><?= e($item['label']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="admin-nav__footer">
        <a href="<?= SITE_URL ?>" class="admin-nav__view-site">&#8617; View Site</a>
        <a href="<?= SITE_URL ?>/logout.php" class="admin-nav__logout">Log out</a>
    </div>
</nav>
