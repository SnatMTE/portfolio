<?php
/**
 * crm/module.php
 *
 * CRM module manifest — consumed by the CMS module loader.
 *
 * @return array Module metadata array.
 */

return [
    'name'        => 'CRM',
    'description' => 'Customer Relationship Management — customers, leads, tasks, companies and messages.',
    'icon'        => '📊',
    'url'         => '/crm/',
    'admin_link'  => '/crm/',
    'admin_menu'  => [
        ['label' => '📊 CRM Dashboard',  'url' => '/crm/'],
        ['label' => '👤 Customers',      'url' => '/crm/customers/'],
        ['label' => '🏢 Companies',      'url' => '/crm/companies/'],
        ['label' => '🎯 Leads',          'url' => '/crm/leads/'],
        ['label' => '✅ Tasks',           'url' => '/crm/tasks/'],
        ['label' => '💬 Messages',       'url' => '/crm/messages/'],
        ['label' => '⏳ Activity Log',   'url' => '/crm/activity.php'],
    ],
];
