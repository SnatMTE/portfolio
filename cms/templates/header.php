<?php
/**
 * cms/templates/header.php
 *
 * Public-facing CMS header (used by login, setup, and page viewer).
 *
 * Expects: $pageTitle (string)
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

if (!defined('CMS_ROOT')) {
    require_once dirname(__DIR__) . '/config.php';
}

$pageTitle ??= CMS_NAME;
$_siteName    = getSetting('site_name', CMS_NAME);
$_siteTagline = getSetting('site_tagline', CMS_TAGLINE);

// Collect nav items: published pages with show_in_menu = 1
$_menuPages = getCMSDB()->query(
    "SELECT title, slug FROM pages WHERE status = 'published' AND show_in_menu = 1 ORDER BY id ASC"
)->fetchAll();

// Active modules for nav links
$_navModules = getActiveModules();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> – <?= e($_siteName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="public-layout">

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= SITE_URL ?>/" class="site-logo">
            <span class="logo-main"><?= e($_siteName) ?></span>
            <span class="logo-tagline"><?= e($_siteTagline) ?></span>
        </a>
        <nav class="primary-nav" aria-label="Primary navigation">
            <ul>
                <?php foreach ($_menuPages as $_mp): ?>
                    <li><a href="<?= SITE_URL ?>/page/<?= e($_mp['slug']) ?>"><?= e($_mp['title']) ?></a></li>
                <?php endforeach; ?>
                <?php foreach ($_navModules as $_mod): ?>
                    <li><a href="<?= e($_mod['url']) ?>"><?= e($_mod['name']) ?></a></li>
                <?php endforeach; ?>
                <?php if (cmsIsLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/admin/">Admin</a></li>
                    <li><a href="<?= SITE_URL ?>/logout.php?csrf=<?= cmsCsrfToken() ?>">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
