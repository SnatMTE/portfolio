<?php
/**
 * cms/index.php
 *
 * CMS public homepage.
 * Displays the published "home" page (slug = 'home').
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$flash = cmsGetFlash();

// Load the Home page
$stmt = getCMSDB()->prepare(
    "SELECT * FROM pages WHERE slug = 'home' AND status = 'published' LIMIT 1"
);
$stmt->execute();
$homePage = $stmt->fetch();

$pageTitle = $homePage ? $homePage['title'] : getSetting('site_name', CMS_NAME);
require_once CMS_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($homePage): ?>
<article class="card">
    <h1><?= e($homePage['title']) ?></h1>
    <div class="page-content">
        <?= $homePage['content'] /* Stored as HTML; output unescaped intentionally */ ?>
    </div>
</article>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <span class="empty-state__icon">&#128640;</span>
        <h2>CMS is running</h2>
        <p>
            <a href="<?= SITE_URL ?>/admin/">Go to Admin Panel</a> to manage content.
        </p>
    </div>
</div>
<?php endif; ?>

<?php require_once CMS_ROOT . '/templates/footer.php'; ?>


<?php require_once CMS_ROOT . '/templates/footer.php'; ?>
