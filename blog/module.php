<?php
/**
 * blog/module.php
 *
 * Module manifest read by the CMS module loader.
 * Return an array of metadata; the CMS will merge this with defaults.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

return [
    'name'        => 'Blog',
    'description' => 'Publish posts, categories, and tags.',
    'icon'        => '&#128221;',
    'admin_link'  => (defined('SITE_URL') ? SITE_URL : '') . '/blog/admin/',
    'url'         => (defined('SITE_URL') ? SITE_URL : '') . '/blog/',
    // Admin menu items that the CMS can render inside its unified sidebar.
    'admin_menu'  => [
        ['label' => 'Dashboard', 'url' => (defined('SITE_URL') ? SITE_URL : '') . '/blog/admin/'],
        ['label' => 'All Posts', 'url' => (defined('SITE_URL') ? SITE_URL : '') . '/blog/admin/posts.php'],
        ['label' => 'New Post',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/blog/admin/create_post.php'],
        ['label' => 'Categories','url' => (defined('SITE_URL') ? SITE_URL : '') . '/blog/admin/categories.php'],
        ['label' => 'Tags',      'url' => (defined('SITE_URL') ? SITE_URL : '') . '/blog/admin/tags.php'],
    ],
];
