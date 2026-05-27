<?php
/**
 * crm/api/leads.php
 *
 * JSON endpoint: list leads or update stage via POST.
 *
 * GET  ?q=<search>       — returns up to 20 leads (id, title, stage)
 * POST {id, stage}       — updates lead stage, returns updated row
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!crmCanAccess()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$db = getCRMDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!crmCanEdit()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $raw = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int) ($raw['id']    ?? 0);
    $stage = trim($raw['stage']   ?? '');

    $validStages = ['new','contacted','qualified','proposal','negotiation','won','lost'];
    if ($id <= 0 || !in_array($stage, $validStages, true)) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid id or stage']);
        exit;
    }

    $db->prepare("UPDATE crm_leads SET stage = :stage, updated_at = datetime('now') WHERE id = :id")
       ->execute([':stage' => $stage, ':id' => $id]);

    crmLogActivity('stage changed', 'lead', $id, $stage);

    $row = $db->prepare("SELECT id, title, stage FROM crm_leads WHERE id = :id");
    $row->execute([':id' => $id]);
    echo json_encode($row->fetch());
    exit;
}

// GET — search leads.
$q = trim($_GET['q'] ?? '');
if ($q === '') { echo json_encode([]); exit; }

$stmt = $db->prepare(
    "SELECT id, title, stage FROM crm_leads
     WHERE title LIKE :q ORDER BY title ASC LIMIT 20"
);
$stmt->execute([':q' => '%' . $q . '%']);
echo json_encode($stmt->fetchAll());
