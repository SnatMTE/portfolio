<?php
/**
 * cms/admin/index.php  —  CMS Dashboard
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$stats   = getCMSStats();
$modules = getActiveModules();
$user    = currentCMSUser();
$flash   = cmsGetFlash();

$pageTitle = 'Dashboard';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p class="subtitle">Welcome back, <strong><?= e($user['username'] ?? 'Admin') ?></strong></p>
</div>

<!-- Stats cards -->
<div class="cards-grid">
    <?php
    $statCards = [
        ['label' => 'Users',   'value' => $stats['users'],   'icon' => '&#128100;', 'link' => SITE_URL . '/admin/users.php'],
        ['label' => 'Pages',   'value' => $stats['pages'],   'icon' => '&#128196;', 'link' => SITE_URL . '/admin/pages.php'],
        ['label' => 'Modules', 'value' => $stats['modules'], 'icon' => '&#128230;', 'link' => SITE_URL . '/admin/modules.php'],
    ];
    foreach ($statCards as $card):
    ?>
        <a href="<?= $card['link'] ?>" class="stat-card">
            <span class="stat-card__icon"><?= $card['icon'] ?></span>
            <span class="stat-card__value"><?= (int) $card['value'] ?></span>
            <span class="stat-card__label"><?= e($card['label']) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Installed modules -->
<?php if ($modules): ?>
<section class="section">
    <h2>Installed Modules</h2>
    <div class="cards-grid">
        <?php foreach ($modules as $mod): ?>
            <div class="module-card">
                <span class="module-card__icon"><?= $mod['icon'] ?></span>
                <div class="module-card__body">
                    <h3><?= e($mod['name']) ?></h3>
                    <?php if ($mod['description']): ?>
                        <p><?= e($mod['description']) ?></p>
                    <?php endif; ?>
                    <div class="module-card__links">
                        <a href="<?= e($mod['url']) ?>" target="_blank">View</a>
                        <a href="<?= e($mod['admin_link']) ?>">Admin</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php else: ?>
<section class="section">
    <h2>No Modules Installed</h2>
    <p>Copy a module folder (blog, forum, or store) into <code><?= e(CMS_ROOT) ?></code> to activate it.</p>
</section>
<?php endif; ?>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
