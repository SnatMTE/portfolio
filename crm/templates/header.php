<?php
/**
 * crm/templates/header.php
 *
 * CRM admin layout header.
 * Outputs <head>, the sidebar navigation, and opens the main content wrapper.
 * Uses the same sidebar pattern as the CMS admin_header.php so the two
 * panels feel visually identical.
 *
 * Expected variables (set before including this file):
 *   $pageTitle  string  – Page title appended to <title>.
 *   $activeNav  string  – Sidebar nav key to highlight (optional).
 */

if (!defined('CRM_ROOT')) {
    require_once dirname(__DIR__) . '/functions.php';
}

$pageTitle  ??= CRM_NAME;
$activeNav  ??= '';
$_crmUser     = currentCMSUser();
$_unread      = crmUnreadMessageCount();

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
    <?php if (!defined('CRM_STANDALONE')): ?>
    <!-- CMS-integrated: inherit the shared site stylesheet -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <?php endif; ?>
    <!-- CRM-specific styles -->
    <link rel="stylesheet" href="<?= CRM_URL ?>/assets/css/crm.css">
</head>
<body class="admin-layout">

<aside class="sidebar" id="crm-sidebar">
    <div class="sidebar__brand">
        <a href="<?= CRM_URL ?>/" class="sidebar__logo">
            <span class="sidebar__logo-icon">&#128200;</span>
            <span><?= CRM_NAME ?></span>
        </a>
        <button class="sidebar__toggle" id="sidebar-close" aria-label="Close navigation">&#215;</button>
    </div>

    <nav class="sidebar__nav" aria-label="CRM navigation">
        <ul>
            <?php foreach ($_navItems as $item): ?>
                <li>
                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>"
                       class="sidebar__link <?= $activeNav === $item['key'] ? 'sidebar__link--active' : '' ?>">
                        <span class="sidebar__link-icon"><?= $item['icon'] ?></span>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (cmsIsAdmin() && !defined('CRM_STANDALONE')): ?>
            <div class="sidebar__section-label">Admin</div>
            <ul>
                <li>
                    <a href="<?= SITE_URL ?>/admin/" class="sidebar__link">
                        <span class="sidebar__link-icon">&#9881;</span>
                        <span>CMS Admin</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    <div class="sidebar__footer">
        <span class="sidebar__user">&#128100; <?= htmlspecialchars($_crmUser['username'] ?? 'User', ENT_QUOTES) ?></span>
        <?php
        $logoutUrl = defined('CRM_STANDALONE')
            ? CRM_URL . '/logout.php'
            : SITE_URL . '/logout.php?csrf=' . crmCsrfToken();
        ?>
        <a href="<?= htmlspecialchars($logoutUrl, ENT_QUOTES) ?>" class="sidebar__logout">Logout</a>
    </div>
</aside>

<main class="admin-main" id="crm-main">
    <!-- Mobile top bar -->
    <div class="crm-topbar">
        <button class="crm-topbar__toggle" id="sidebar-open" aria-label="Open navigation">&#9776;</button>
        <span class="crm-topbar__title"><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></span>
        <a href="<?= CRM_URL ?>/messages/" class="crm-topbar__messages">
            &#128140;<?= $_unread > 0 ? " <span class=\"crm-badge\">{$_unread}</span>" : '' ?>
        </a>
    </div>

    <div class="admin-content">
