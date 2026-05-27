<?php
/**
 * core/file_helper.php
 *
 * Utility functions for file management within the Download Manager.
 * Covers safe deletion, storage path resolution, and filesystem queries.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Returns the absolute storage path for a stored filename.
 *
 * Rejects any path that would escape the storage directory to prevent
 * path traversal. Returns null for invalid names.
 *
 * @param string $storedName  Filename as stored in the database (no path).
 *
 * @return string|null  Absolute path, or null if the name is invalid.
 */
function storagePath(string $storedName): ?string
{
    // Prevent any form of directory traversal in the stored filename
    if ($storedName === '' || str_contains($storedName, '/') || str_contains($storedName, '\\')) {
        return null;
    }

    $path = DM_STORAGE . '/' . $storedName;

    // Confirm the resolved path is still inside the storage directory
    $real = realpath(DM_STORAGE);
    if ($real === false) {
        return null;
    }

    // realpath() on the constructed path (file may not exist yet)
    $resolved = realpath($path);
    if ($resolved !== false && !str_starts_with($resolved, $real . DIRECTORY_SEPARATOR)) {
        return null;
    }

    return $path;
}

/**
 * Deletes a file from the storage directory.
 *
 * Only deletes files that physically exist within DM_STORAGE.
 * Silent no-op when the file does not exist (already cleaned up).
 *
 * @param string $storedName  Filename stored in the database.
 *
 * @return bool  True on success, false when deletion failed.
 */
function deleteStoredFile(string $storedName): bool
{
    // Skip the demo index placeholder
    if ($storedName === 'index.html') {
        return true;
    }

    $path = storagePath($storedName);
    if ($path === null) {
        return false;
    }

    if (!file_exists($path)) {
        // Already gone — not really an error
        return true;
    }

    return unlink($path);
}

/**
 * Returns the actual file size in bytes for a stored file.
 *
 * Useful when re-syncing the database record after a file is replaced.
 *
 * @param string $storedName  Filename stored in the database.
 *
 * @return int  File size in bytes, or 0 when the file cannot be read.
 */
function storedFileSize(string $storedName): int
{
    $path = storagePath($storedName);
    if ($path === null || !file_exists($path)) {
        return 0;
    }
    return (int) filesize($path);
}

/**
 * Cleans up expired download tokens from the database.
 *
 * Intended to be called periodically (e.g. from the admin dashboard).
 * Removes all token rows whose expires_at is in the past.
 *
 * @return int  Number of expired token rows removed.
 */
function pruneExpiredTokens(): int
{
    $stmt = getDB()->prepare(
        "DELETE FROM dm_download_tokens WHERE expires_at <= datetime('now')"
    );
    $stmt->execute();
    return $stmt->rowCount();
}
