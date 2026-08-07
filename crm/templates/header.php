<?php
/**
 * crm/templates/header.php
 *
 * CRM layout header.
 * Outputs <head>, the top horizontal navigation bar, and opens the main
 * content wrapper.
 *
 * Expected variables (set before including this file):
 *   $pageTitle  string  – Page title appended to <title>.
 *   $activeNav  string  – Nav key to highlight (optional).
 */

if (!defined('CRM_ROOT')) {
    require_once dirname(__DIR__) . '/functions.php';
}

$pageTitle  ??= CRM_NAME;
$activeNav  ??= '';
$_crmUser     = currentCMSUser();
$_unread      = crmUnreadMessageCount();
$logoutUrl    = defined('CRM_STANDALONE')
    ? CRM_URL . '/logout.php'
    : SITE_URL . '/logout.php?csrf=' . crmCsrfToken();

$_navItems = [
    ['key' => 'dashboard',  'url' => CRM_URL . '/',              'icon' => '&#128200;', 'label' => 'Dashboard'],
    ['key' => 'customers',  'url' => CRM_URL . '/customers/',    'icon' => '&#128100;', 'label' => 'Customers'],
    ['key' => 'companies',  'url' => CRM_URL . '/companies/',    'icon' => '&#127970;', 'label' => 'Companies'],
    ['key' => 'leads',      'url' => CRM_URL . '/leads/',        'icon' => '&#127919;', 'label' => 'Leads'],
    ['key' => 'tasks',      'url' => CRM_URL . '/tasks/',        'icon' => '&#9989;',   'label' => 'Tasks'],
    ['key' => 'messages',   'url' => CRM_URL . '/messages/',     'icon' => '&#128140;', 'label' => 'Messages' . ($_unread > 0 ? " <span class=\"crm-badge\">{$_unread}</span>" : '')],
    ['key' => 'activity',   'url' => CRM_URL . '/activity.php', 'icon' => '&#128203;', 'label' => 'Activity Log'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> – <?= CRM_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (defined('CRM_STANDALONE')): ?>
    <!-- Standalone mode: use CRM's own base stylesheet -->
    <link rel="stylesheet" href="<?= CRM_URL ?>/assets/css/style.css">
    <?php else: ?>
    <!-- CMS-integrated: inherit the shared site stylesheet -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <?php endif; ?>
    <!-- CRM-specific styles -->
    <link rel="stylesheet" href="<?= CRM_URL ?>/assets/css/crm.css">
</head>
<body>

<!-- Horizontal top navigation bar -->
<header class="crm-navbar" id="crm-navbar">
    <div class="crm-navbar__inner">
        <a href="<?= CRM_URL ?>/" class="crm-navbar__brand">
            <span class="crm-navbar__logo">&#128200;</span>
            <span class="crm-navbar__title"><?= CRM_NAME ?></span>
        </a>

        <nav class="crm-navbar__nav" id="crm-nav" aria-label="CRM navigation">
            <ul>
                <?php foreach ($_navItems as $item): ?>
                    <li>
                        <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>"
                           class="crm-navbar__link <?= $activeNav === $item['key'] ? 'crm-navbar__link--active' : '' ?>">
                            <span class="crm-navbar__link-icon"><?= $item['icon'] ?></span>
                            <span><?= $item['label'] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php if (cmsIsAdmin() && !defined('CRM_STANDALONE')): ?>
                    <li>
                        <a href="<?= SITE_URL ?>/admin/" class="crm-navbar__link">
                            <span class="crm-navbar__link-icon">&#9881;</span>
                            <span>CMS Admin</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="crm-navbar__right">
            <span class="crm-navbar__user">&#128100; <?= htmlspecialchars($_crmUser['username'] ?? 'User', ENT_QUOTES) ?></span>
            <a href="<?= htmlspecialchars($logoutUrl, ENT_QUOTES) ?>" class="crm-navbar__logout">Logout</a>
        </div>

        <button class="crm-navbar__toggle" id="crm-nav-toggle" aria-label="Toggle navigation">&#9776;</button>
    </div>
</header>

<main class="admin-main">
    <div class="admin-content">
