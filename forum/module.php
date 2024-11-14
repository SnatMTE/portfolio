<?php
/**
 * forum/module.php
 *
 * Module manifest read by the CMS module loader.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

return [
    'name'        => 'Forum',
    'description' => 'Community threads and discussions.',
    'icon'        => '&#128172;',
    'admin_link'  => (defined('SITE_URL') ? SITE_URL : '') . '/forum/admin/',
    'url'         => (defined('SITE_URL') ? SITE_URL : '') . '/forum/',
    'admin_menu'  => [
        ['label' => 'Dashboard',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/forum/admin/'],
        ['label' => 'Categories', 'url' => (defined('SITE_URL') ? SITE_URL : '') . '/forum/admin/categories.php'],
        ['label' => 'Threads',    'url' => (defined('SITE_URL') ? SITE_URL : '') . '/forum/admin/threads.php'],
        ['label' => 'Posts',      'url' => (defined('SITE_URL') ? SITE_URL : '') . '/forum/admin/posts.php'],
        ['label' => 'Users',      'url' => (defined('SITE_URL') ? SITE_URL : '') . '/forum/admin/users.php'],
    ],
];
