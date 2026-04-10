<?php
/**
 * admin/check_slug.php
 *
 * AJAX endpoint to validate and suggest a CMS page slug (pretty URL).
 * Expects query parameter `slug` and optional `exclude_id` to ignore
 * an existing page when editing.
 */

require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

$raw = trim((string) ($_GET['slug'] ?? ''));
$excludeId = isset($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : 0;

// Normalize using server-side slugify
$slug = cmsSlugify($raw);

$db = getCMSDB();
$stmt = $db->prepare("SELECT id FROM pages WHERE slug = :slug" . ($excludeId ? " AND id != :id" : "") . " LIMIT 1");
$params = [':slug' => $slug];
if ($excludeId) $params[':id'] = $excludeId;
$stmt->execute($params);
$exists = (bool) $stmt->fetch();

echo json_encode([
    'requested' => $raw,
    'slug'      => $slug,
    'available' => !$exists,
]);

exit;
