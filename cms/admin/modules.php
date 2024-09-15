<?php
/**
 * cms/admin/modules.php  —  Installed module overview
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSAdmin();

$modules = getActiveModules();

$pageTitle = 'Modules';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<div class="page-header">
    <h1>Modules</h1>
</div>

<?php if ($modules): ?>
    <div class="cards-grid">
        <?php foreach ($modules as $mod): ?>
            <div class="module-card module-card--full">
                <span class="module-card__icon"><?= $mod['icon'] ?></span>
                <div class="module-card__body">
                    <h3><?= e($mod['name']) ?></h3>
                    <p class="module-card__path"><code><?= e($mod['path']) ?></code></p>
                    <?php if ($mod['description']): ?>
                        <p><?= e($mod['description']) ?></p>
                    <?php endif; ?>
                    <div class="module-card__links">
                        <a href="<?= e($mod['url']) ?>" class="btn btn--sm" target="_blank">View Site</a>
                        <a href="<?= e($mod['admin_link']) ?>" class="btn btn--sm btn--primary">Admin Panel</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-state__icon">&#128230;</span>
            <h2>No Modules Installed</h2>
            <p>
                Copy a module folder into <code><?= e(CMS_ROOT) ?></code> to activate it.<br>
                Supported modules: <strong>blog</strong>, <strong>forum</strong>, <strong>store</strong>.
            </p>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="margin-top:1.5rem">
    <h3>Module Integration Rules</h3>
    <ul class="info-list">
        <li>Each module must reside directly inside the CMS directory.</li>
        <li>Modules use the CMS login — no separate login pages.</li>
        <li>Authentication is shared via the CMS session.</li>
        <li>The CMS admin panel links directly to each module's admin.</li>
    </ul>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
