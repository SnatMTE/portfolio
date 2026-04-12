<?php
/**
 * templates/header.php
 *
 * Renders the HTML <head> section and shared store navigation bar.
 * Expects the consuming page to have set:
 *
 *   $pageTitle  (string)  – Text appended to the <title> tag.
 *   $metaDesc   (string)  – Optional meta description.
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

$cartCount  = getCartCount();
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
                <li><a href="<?= SITE_URL ?>">Shop</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="<?= SITE_URL ?>?category=<?= e($cat['slug']) ?>">
                            <?= e($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form class="header-search" action="<?= SITE_URL ?>" method="get" role="search">
                <label for="header-search-input" class="sr-only">Search products</label>
                <input
                    id="header-search-input"
                    type="search"
                    name="q"
                    placeholder="Search products…"
                    value="<?= e($_GET['q'] ?? '') ?>"
                    maxlength="150"
                >
                <button type="submit" aria-label="Submit search">&#8981;</button>
            </form>

            <a href="<?= SITE_URL ?>/cart.php" class="cart-link" aria-label="View cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
                Cart
            </a>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">
