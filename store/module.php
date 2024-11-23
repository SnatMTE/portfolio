<?php
/**
 * store/module.php
 *
 * Module manifest read by the CMS module loader.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

return [
    'name'        => 'Store',
    'description' => 'Product catalogue and order management.',
    'icon'        => '&#128722;',
    'admin_link'  => (defined('SITE_URL') ? SITE_URL : '') . '/store/admin/',
    'url'         => (defined('SITE_URL') ? SITE_URL : '') . '/store/',
    'admin_menu'  => [
        ['label' => 'Dashboard', 'url' => (defined('SITE_URL') ? SITE_URL : '') . '/store/admin/'],
        ['label' => 'Products',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/store/admin/products.php'],
        ['label' => 'Orders',    'url' => (defined('SITE_URL') ? SITE_URL : '') . '/store/admin/orders.php'],
        ['label' => 'Settings',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/store/admin/settings.php'],
    ],
];
