<?php
/**
 * templates/header.php
 *
 * Renders the HTML <head> section and site navigation bar shared by every
 * public-facing page. Expects the consuming page to have set:
 *
 *   $pageTitle   (string)  – Text appended to the <title> tag.
 *   $metaDesc    (string)  – Optional meta description (defaults to site tagline).
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config.php';
}

$pageTitle ??= SITE_NAME;
$metaDesc  ??= SITE_TAGLINE;
$fullTitle   = ($pageTitle !== SITE_NAME) ? e($pageTitle) . ' – ' . e(SITE_NAME) : e(SITE_NAME);

$categories = getAllCategories();
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
    <link rel="alternate" type="application/rss+xml" title="<?= e(SITE_NAME) ?> RSS Feed" href="<?= SITE_URL ?>/rss">
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
                <li><a href="<?= SITE_URL ?>">Home</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="<?= SITE_URL ?>/category/<?= e($cat['slug']) ?>">
                            <?= e($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li>
                    <a href="<?= SITE_URL ?>/rss" class="rss-link" title="RSS Feed">RSS</a>
                </li>
            </ul>

            <form class="header-search" action="<?= SITE_URL ?>/search" method="get" role="search">
                <label for="header-search-input" class="sr-only">Search posts</label>
                <input
                    id="header-search-input"
                    type="search"
                    name="q"
                    placeholder="Search…"
                    value="<?= e($_GET['q'] ?? '') ?>"
                    maxlength="150"
                >
                <button type="submit" aria-label="Submit search">&#8981;</button>
            </form>
        </nav>
    </div>
</header>

<main class="site-main" id="main-content">
    <div class="container">
