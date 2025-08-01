<?php
/**
 * core/download_handler.php
 *
 * Streams a file to the browser via PHP, bypassing direct URL access.
 * This is the only legitimate way a stored file reaches the end user.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Validates that a download record exists and is accessible.
 *
 * Checks visibility: private files require either an active admin session
 * or a valid download token. Returns the record on success, null on failure.
 *
 * @param int    $id     Download record ID.
 * @param string $token  Optional access token from the query string.
 *
 * @return array<string,mixed>|null  Download record or null when access is denied.
 */
function resolveDownload(int $id, string $token = ''): ?array
{
    $row = getDownload($id);

    if ($row === null) {
        return null;
    }

    // Public files are always accessible
    if ($row['visibility'] === 'public') {
        return $row;
    }

    // Private files: admin session or valid token required
    if (!empty($_SESSION['admin_id'])) {
        return $row;
    }

    if ($token !== '' && validateDownloadToken($id, $token)) {
        return $row;
    }

    return null;
}

/**
 * Streams a stored file to the browser with appropriate HTTP headers.
 *
 * Sets Content-Disposition to attachment so the browser prompts a save
 * dialogue rather than attempting to display the file inline. Falls back
 * to octet-stream MIME when the stored type is empty.
 *
 * This function terminates execution after streaming completes.
 *
 * @param string $storedName   Filename in the storage directory (no path).
 * @param string $originalName Filename presented to the user on download.
 * @param string $mimeType     MIME type of the file.
 * @param int    $fileSize     File size in bytes (used for Content-Length header).
 *
 * @return never
 */
function serveFile(
    string $storedName,
    string $originalName,
    string $mimeType,
    int    $fileSize
): never {
    $filePath = DM_STORAGE . '/' . $storedName;

    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        exit('File not found.');
    }

    // Strip any path separators from the original name to prevent header injection
    $safeOriginal = str_replace(["\r", "\n", '"', ';'], '', basename($originalName));
    $mime         = ($mimeType !== '') ? $mimeType : 'application/octet-stream';

    // Disable output buffering to avoid loading the entire file into RAM
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: '        . $mime);
    header('Content-Disposition: attachment; filename="' . $safeOriginal . '"');
    header('Content-Length: '      . $fileSize);
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');

    readfile($filePath);
    exit;
}
