<?php
/**
 * module.php
 *
 * CMS module manifest. Read by the CMS module loader when this directory
 * is placed inside /cms/downloads/.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

return [
    'name'        => 'Downloads',
    'description' => 'Secure file hosting, uploads, and tracked downloads.',
    'icon'        => '&#128190;',
    'admin_link'  => (defined('SITE_URL') ? SITE_URL : '') . '/downloads/admin/',
    'url'         => (defined('SITE_URL') ? SITE_URL : '') . '/downloads/',
    'admin_menu'  => [
        ['label' => 'Dashboard',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/downloads/admin/'],
        ['label' => 'All Files',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/downloads/admin/files.php'],
        ['label' => 'Upload',     'url' => (defined('SITE_URL') ? SITE_URL : '') . '/downloads/upload.php'],
    ],
];
