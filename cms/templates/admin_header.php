<?php
/**
 * cms/templates/admin_header.php
 *
 * Renders the HTML <head>, sidebar navigation, and page wrapper
 * for all CMS admin pages.
 *
 * Expects: $pageTitle (string)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

if (!defined('CMS_ROOT')) {
    require_once dirname(__DIR__) . '/config.php';
}

$pageTitle ??= 'CMS';
$_cmsUser   = currentCMSUser();
$_modules   = getActiveModules();
$_navItems  = [
    ['url' => SITE_URL . '/admin/',          'label' => '&#128200; Dashboard', 'key' => 'index'],
    ['url' => SITE_URL . '/admin/users.php', 'label' => '&#128100; Users',     'key' => 'users'],
    ['url' => SITE_URL . '/admin/roles.php', 'label' => '&#127959; Roles',     'key' => 'roles'],
    ['url' => SITE_URL . '/admin/pages.php', 'label' => '&#128196; Pages',     'key' => 'pages'],
    ['url' => SITE_URL . '/admin/settings.php','label'=> '&#9881; Settings',   'key' => 'settings'],
];

$_currentScript = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> – CMS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="admin-layout">

<aside class="sidebar">
    <div class="sidebar__brand">
        <a href="<?= SITE_URL ?>/admin/" class="sidebar__logo">
            <span class="sidebar__logo-icon">&#9881;</span>
            <span>CMS Admin</span>
        </a>
    </div>

    <nav class="sidebar__nav" aria-label="Admin navigation">
        <ul>
            <?php foreach ($_navItems as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>"
                       class="sidebar__link <?= $_currentScript === $item['key'] ? 'sidebar__link--active' : '' ?>">
                        <?= $item['label'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($_modules): ?>
            <?php
            // Show module sections so modules can inject admin links
            $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH);
            foreach ($_modules as $mod):
                // If the module provides an `admin_menu` array in its manifest, render it
                if (!empty($mod['admin_menu']) && is_array($mod['admin_menu'])):
            ?>
                <div class="sidebar__section-title"><?= $mod['icon'] ?> <?= e($mod['name']) ?></div>
                <ul>
                    <?php foreach ($mod['admin_menu'] as $mi):
                        $miUrl = $mi['url'] ?? ($mi['href'] ?? $mod['admin_link']);
                        $miPath = parse_url($miUrl, PHP_URL_PATH);
                        $isActive = rtrim($currentPath, '/') === rtrim($miPath, '/');
                    ?>
                        <li>
                            <a href="<?= e($miUrl) ?>" class="sidebar__link <?= $isActive ? 'sidebar__link--active' : '' ?>">
                                <?= e($mi['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php
                else:
            ?>
                <div class="sidebar__section-title"><?= $mod['icon'] ?> <?= e($mod['name']) ?></div>
                <ul>
                    <li>
                        <a href="<?= e($mod['admin_link']) ?>" class="sidebar__link <?= (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/' . $mod['key'] . '/admin') !== false) ? 'sidebar__link--active' : '' ?>">
                            <?= $mod['icon'] ?> <?= e($mod['name']) ?>
                        </a>
                    </li>
                </ul>
            <?php
                endif;
            endforeach;
            ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar__footer">
        <span class="sidebar__user">&#128100; <?= e($_cmsUser['username'] ?? 'Admin') ?></span>
        <a href="<?= SITE_URL ?>/logout.php?csrf=<?= cmsCsrfToken() ?>" class="sidebar__logout">Logout</a>
    </div>
</aside>

<main class="admin-main">
    <div class="admin-content">
