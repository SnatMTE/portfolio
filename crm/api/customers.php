<?php
/**
 * crm/api/customers.php
 *
 * JSON search endpoint for customer autocomplete.
 * GET ?q=<search_term> — returns up to 20 matching customers.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!crmCanAccess()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$q  = trim($_GET['q'] ?? '');
$db = getCRMDB();

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $db->prepare(
    "SELECT id, first_name || ' ' || last_name AS name, email
     FROM crm_customers
     WHERE first_name LIKE :q OR last_name LIKE :q OR email LIKE :q
     ORDER BY last_name ASC LIMIT 20"
);
$stmt->execute([':q' => '%' . $q . '%']);
echo json_encode($stmt->fetchAll());
