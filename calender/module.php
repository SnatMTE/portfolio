<?php
/**
 * calendar/module.php
 *
 * Module manifest read by the CMS module loader.
 * Return value is an associative array of metadata.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

return [
    'name'        => 'Calendar',
    'description' => 'Manage events, import/export .ics, and provide calendar sync feeds.',
    'icon'        => '&#128197;',
    'admin_link'  => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/admin/',
    'url'         => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/',
    'admin_menu'  => [
        ['label' => 'Dashboard',   'url' => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/admin/'],
        ['label' => 'All Events',  'url' => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/admin/events.php'],
        ['label' => 'New Event',   'url' => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/create_event.php'],
        ['label' => 'Import .ics', 'url' => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/import.php'],
        ['label' => 'Sync Tokens', 'url' => (defined('SITE_URL') ? SITE_URL : '') . '/calendar/admin/tokens.php'],
    ],
];
