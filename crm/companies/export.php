<?php
/**
 * crm/companies/export.php
 *
 * Exports the full company list to CSV.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db   = getCRMDB();
$rows = $db->query(
    "SELECT name, industry, website, phone, email, address, city, country, created_at
     FROM crm_companies ORDER BY name ASC"
)->fetchAll();

$output = [['Name','Industry','Website','Phone','Email','Address','City','Country','Created At']];
foreach ($rows as $r) { $output[] = array_values($r); }

crmLogActivity('exported', 'company', 0, count($rows) . ' companies exported to CSV');
crmExportCsv($output, 'companies_' . date('Y-m-d') . '.csv');
