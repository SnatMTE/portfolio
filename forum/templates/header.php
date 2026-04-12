<?php
/**
 * templates/header.php
 *
 * Renders the HTML <head> section, site navigation, and opens the
 * main content wrapper. Included at the top of every public page.
 *
 * Expects the consuming page to have optionally set:
 *   $pageTitle  (string)  - Text appended to the <title> tag.
 *   $metaDesc   (string)  - Meta description.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

if (!function_exists('e')) {
    require_once dirname(__DIR__) . '/functions.php';
}

$pageTitle ??= FORUM_NAME;
$metaDesc  ??= FORUM_TAGLINE;
$fullTitle   = ($pageTitle !== FORUM_NAME)
    ? e($pageTitle) . ' - ' . e(FORUM_NAME)
    : e(FORUM_NAME);

$navCategories = getCategories();
$currentUser   = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($metaDesc) ?>">
    <title><?= $fullTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= SITE_URL ?>" class="site-logo">
            <span class="logo-main"><?= e(FORUM_NAME) ?></span>
            <span class="logo-tagline"><?= e(FORUM_TAGLINE) ?></span>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="primary-nav" aria-label="Primary navigation">
            <ul>
                <li><a href="<?= SITE_URL ?>">Home</a></li>
                <?php foreach ($navCategories as $navCat): ?>
                    <li>
                        <a href="<?= SITE_URL ?>/category.php?slug=<?= e($navCat['slug']) ?>">
                            <?= e($navCat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li><a href="<?= SITE_URL ?>/search.php">Search</a></li>
            </ul>
        </nav>

        <div class="header-auth">
            <?php if ($currentUser): ?>
                <div class="user-menu">
                    <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $currentUser['id'] ?>"
                       class="user-menu__name"><?= e($currentUser['username']) ?></a>
                    <?php if (isAdmin()): ?>
                        <a href="<?= SITE_URL ?>/admin/" class="btn btn--outline btn--sm">Admin</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/logout.php" class="btn btn--sm btn--ghost">Log out</a>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn--outline btn--sm">Log in</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn--primary btn--sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="site-main">
    <div class="container">

<?php
$flash = getFlash();
if ($flash): ?>
<div class="alert alert--<?= e($flash['type']) ?>" role="alert">
    <?= e($flash['message']) ?>
</div>
<?php endif; ?>
