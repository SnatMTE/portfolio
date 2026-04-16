<?php
/**
 * download.php
 *
 * Secure file download handler. Files are never served directly from storage;
 * all downloads go through this script, which validates permissions before
 * streaming the file.
 *
 * Query parameters:
 *   id     (int)     Required. Download record ID.
 *   token  (string)  Optional. Access token for private files.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/core/download_handler.php';

// Validate that `id` is a positive integer before touching the database
$id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$token = trim($_GET['token'] ?? '');

if ($id === false || $id === null) {
    http_response_code(400);
    exit('Invalid request.');
}

$file = resolveDownload($id, $token);

if ($file === null) {
    // Private file with no valid session/token, or record not found
    http_response_code(404);
    exit('File not found or access denied.');
}

// Bump the counter before streaming so an interrupted transfer still counts
incrementDownloadCount($id);

serveFile(
    $file['file_path'],
    $file['original_name'],
    $file['mime_type'],
    (int) $file['file_size']
);
