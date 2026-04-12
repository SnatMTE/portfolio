<?php
/**
 * templates/header.php
 *
 * Shared HTML <head> and navigation bar for every public-facing Calendar page.
 *
 * Expects the consuming page to have set:
 *   $pageTitle  (string)  – Appended to the <title> tag.
 *   $metaDesc   (string)  – Optional meta description.
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

$pageTitle ??= SITE_NAME;
$metaDesc  ??= SITE_TAGLINE;
$fullTitle   = ($pageTitle !== SITE_NAME)
    ? e($pageTitle) . ' – ' . e(SITE_NAME)
    : e(SITE_NAME);
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
            <span class="logo-main"><?= e(SITE_NAME) ?></span>
            <span class="logo-tagline"><?= e(SITE_TAGLINE) ?></span>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="primary-nav" aria-label="Primary navigation">
            <ul>
                <li><a href="<?= SITE_URL ?>">Calendar</a></li>
                <li><a href="<?= SITE_URL ?>/create_event.php">+ New Event</a></li>
                <li><a href="<?= SITE_URL ?>/import.php">Import</a></li>
                <li><a href="<?= SITE_URL ?>/export.php">Export</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/admin/">Admin</a></li>
                    <?php if (defined('CMS_URL')): ?>
                        <li><a href="<?= CMS_URL ?>/logout.php">Log out</a></li>
                    <?php else: ?>
                        <li><a href="<?= SITE_URL ?>/logout.php">Log out</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!defined('CMS_ROOT')): ?>
                        <li><a href="<?= SITE_URL ?>/login.php">Login</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
