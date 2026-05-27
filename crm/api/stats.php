<?php
/**
 * crm/api/stats.php
 *
 * Returns CRM dashboard stats as JSON.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!crmCanAccess()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

echo json_encode(getCRMStats(getCRMDB()));
