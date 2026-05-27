<?php
/**
 * crm/leads/export.php
 *
 * Exports all leads to CSV.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db   = getCRMDB();
$rows = $db->query(
    "SELECT l.title, l.stage, l.priority, l.value, l.currency,
            l.contact_name, l.contact_email, l.contact_phone,
            c.first_name || ' ' || c.last_name AS customer,
            co.name AS company,
            u.username AS assigned_to,
            l.close_date, l.source, l.created_at
     FROM crm_leads l
     LEFT JOIN crm_customers c  ON c.id  = l.customer_id
     LEFT JOIN crm_companies co ON co.id = l.company_id
     LEFT JOIN users u          ON u.id  = l.assigned_to
     ORDER BY l.created_at DESC"
)->fetchAll();

$output = [['Title','Stage','Priority','Value','Currency','Contact Name','Contact Email','Contact Phone','Customer','Company','Assigned To','Close Date','Source','Created At']];
foreach ($rows as $r) { $output[] = array_values($r); }

crmLogActivity('exported', 'lead', 0, count($rows) . ' leads exported to CSV');
crmExportCsv($output, 'leads_' . date('Y-m-d') . '.csv');
