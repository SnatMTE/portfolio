<?php
/**
 * core/upload_handler.php
 *
 * Handles secure file upload processing.
 * Validates MIME type, file size, and renames files to prevent collisions.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Processes an uploaded file and moves it to the storage directory.
 *
 * Validates:
 *   - No upload error
 *   - File size within DM_MAX_UPLOAD
 *   - MIME type against DM_ALLOWED_TYPES (checked with finfo, not just extension)
 *
 * On success, returns an associative array of file metadata ready to be
 * inserted into dm_downloads. On failure, throws a RuntimeException.
 *
 * @param array<string,mixed> $fileEntry  One entry from $_FILES (e.g. $_FILES['file']).
 *
 * @return array{file_path:string,original_name:string,file_size:int,mime_type:string}
 *
 * @throws RuntimeException  When validation fails or the file cannot be moved.
 */
function handleUpload(array $fileEntry): array
{
    // Catch PHP upload errors before anything else
    if (!isset($fileEntry['error'])) {
        throw new RuntimeException('No file was submitted.');
    }

    $errorCode = $fileEntry['error'];

    if ($errorCode !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server\'s maximum upload size.',
            UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form\'s maximum upload size.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temporary directory.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        throw new RuntimeException($messages[$errorCode] ?? 'An unknown upload error occurred.');
    }

    $tmpPath      = $fileEntry['tmp_name'] ?? '';
    $originalName = basename($fileEntry['name'] ?? 'upload');
    $fileSize     = (int) ($fileEntry['size'] ?? 0);

    // tmp_name must be a genuine uploaded file — is_uploaded_file prevents
    // path traversal attacks via crafted tmp_name values
    if (!is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid upload. The file may have been tampered with.');
    }

    // Size check against our configured limit
    if ($fileSize > DM_MAX_UPLOAD) {
        throw new RuntimeException(
            'File is too large. Maximum allowed size is ' . formatFileSize(DM_MAX_UPLOAD) . '.'
        );
    }

    // Detect MIME via finfo rather than trusting the browser-supplied value
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);

    if (!in_array($mimeType, DM_ALLOWED_TYPES, true)) {
        throw new RuntimeException(
            'File type "' . htmlspecialchars($mimeType, ENT_QUOTES, 'UTF-8') . '" is not allowed.'
        );
    }

    // Generate a collision-proof storage filename, preserving the extension
    $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $ext      = preg_replace('/[^a-z0-9]/', '', $ext); // strip anything suspicious from the extension
    $safeName = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');

    $destination = DM_STORAGE . '/' . $safeName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        throw new RuntimeException('Failed to store the uploaded file. Check storage directory permissions.');
    }

    // Verify the actual file size on disk as a belt-and-braces check
    $storedSize = filesize($destination);
    if ($storedSize === false) {
        $storedSize = $fileSize;
    }

    return [
        'file_path'     => $safeName,
        'original_name' => $originalName,
        'file_size'     => (int) $storedSize,
        'mime_type'     => $mimeType,
    ];
}
