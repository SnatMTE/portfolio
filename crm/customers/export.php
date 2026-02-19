<?php
/**
 * crm/customers/export.php
 *
 * Streams the customer list as a CSV download.
 * Respects the same search/status filters as the list view.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db     = getCRMDB();
$search = trim($_GET['q']      ?? '');
$status = trim($_GET['status'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[]      = "(c.first_name LIKE :q OR c.last_name LIKE :q OR c.email LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
if ($status !== '') {
    $where[]           = "c.status = :status";
    $params[':status'] = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare(
    "SELECT c.first_name, c.last_name, c.email, c.phone, c.mobile, c.job_title,
            c.address, c.city, c.country, c.status, c.source, c.created_at,
            co.name AS company, u.username AS assigned_to
     FROM crm_customers c
     LEFT JOIN crm_companies co ON co.id = c.company_id
     LEFT JOIN users u ON u.id = c.assigned_to
     {$whereClause}
     ORDER BY c.last_name ASC, c.first_name ASC"
);
$stmt->execute($params);

$rows   = $stmt->fetchAll();
$output = [
    ['First Name','Last Name','Email','Phone','Mobile','Job Title','Address','City','Country','Status','Source','Company','Assigned To','Created At'],
];
foreach ($rows as $r) {
    $output[] = array_values($r);
}

crmLogActivity('exported', 'customer', 0, count($rows) . ' customers exported to CSV');
crmExportCsv($output, 'customers_' . date('Y-m-d') . '.csv');
