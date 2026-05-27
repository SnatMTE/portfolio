<?php
/**
 * functions.php
 *
 * Global helper functions for the Download Manager module.
 * All database access uses PDO prepared statements.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// ===========================================================================
// Output / string helpers
// ===========================================================================

/**
 * Escapes a string for safe HTML output.
 *
 * @param string $string  Raw input string.
 *
 * @return string  HTML-safe string.
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirects the browser to a URL and terminates execution.
 *
 * @param string $url  Destination URL.
 *
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Formats a UTC database date string for human-readable display.
 *
 * @param string $dateString  SQLite datetime string.
 * @param string $format      PHP date format.
 *
 * @return string  Formatted date.
 */
function formatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Converts a byte count into a human-readable file size string.
 *
 * @param int $bytes  File size in bytes.
 *
 * @return string  e.g. "4.2 MB", "780 KB".
 */
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

// ===========================================================================
// CSRF protection
// ===========================================================================

/**
 * Generates (or reuses) a CSRF token stored in the session.
 *
 * @return string  64-character hex token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes is cryptographically secure
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the session value.
 *
 * Uses hash_equals to prevent timing attacks.
 *
 * @param string $submitted  Token value from the form.
 *
 * @return bool  True when the token is valid.
 */
function validateCsrf(string $submitted): bool
{
    $stored = $_SESSION['csrf_token'] ?? '';
    return $stored !== '' && hash_equals($stored, $submitted);
}

// ===========================================================================
// Flash messages
// ===========================================================================

/**
 * Stores a one-time flash message in the session.
 *
 * @param string $message  Message text.
 * @param string $type     'success' | 'error' | 'info'.
 *
 * @return void
 */
function flashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['dm_flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieves and clears the stored flash message.
 *
 * @return array{message:string,type:string}|null  Flash data or null.
 */
function getFlash(): ?array
{
    if (isset($_SESSION['dm_flash'])) {
        $flash = $_SESSION['dm_flash'];
        unset($_SESSION['dm_flash']);
        return $flash;
    }
    return null;
}

/**
 * Renders the flash message as HTML if one is present.
 *
 * @return void
 */
function renderFlash(): void
{
    $flash = getFlash();
    if ($flash === null) {
        return;
    }
    $type = in_array($flash['type'], ['success', 'error', 'info'], true)
        ? $flash['type']
        : 'info';
    echo '<div class="alert alert--' . $type . '" role="alert">' . e($flash['message']) . '</div>';
}

// ===========================================================================
// File type helpers
// ===========================================================================

/**
 * Returns a CSS class name used to colour-code file type icons.
 *
 * @param string $mimeType  MIME type string.
 *
 * @return string  CSS modifier class, e.g. 'pdf', 'archive', 'image'.
 */
function fileTypeClass(string $mimeType): string
{
    if (str_contains($mimeType, 'pdf'))                  return 'pdf';
    if (str_contains($mimeType, 'zip')
        || str_contains($mimeType, 'tar')
        || str_contains($mimeType, 'gzip'))              return 'archive';
    if (str_starts_with($mimeType, 'image/'))            return 'image';
    if (str_starts_with($mimeType, 'text/'))             return 'text';
    if (str_starts_with($mimeType, 'audio/'))            return 'audio';
    if (str_starts_with($mimeType, 'video/'))            return 'video';
    return 'generic';
}

/**
 * Returns a Unicode icon character for a given MIME type.
 *
 * @param string $mimeType  MIME type string.
 *
 * @return string  Unicode icon.
 */
function fileTypeIcon(string $mimeType): string
{
    $class = fileTypeClass($mimeType);
    return match ($class) {
        'pdf'     => '&#128196;',
        'archive' => '&#128230;',
        'image'   => '&#128247;',
        'text'    => '&#128196;',
        'audio'   => '&#127911;',
        'video'   => '&#127916;',
        default   => '&#128190;',
    };
}

// ===========================================================================
// Download record queries
// ===========================================================================

/**
 * Retrieves a paginated list of download records.
 *
 * @param string $visibility  'public' | 'private' | 'all'.
 * @param string $search      Optional search term (title or description).
 * @param string $category    Optional category filter.
 * @param int    $limit       Maximum rows to return.
 * @param int    $offset      Row offset for pagination.
 *
 * @return list<array<string,mixed>>  Array of download rows.
 */
function getDownloads(
    string $visibility = 'public',
    string $search     = '',
    string $category   = '',
    int    $limit      = 20,
    int    $offset     = 0
): array {
    $where  = [];
    $params = [];

    if ($visibility !== 'all') {
        $where[]              = 'visibility = :visibility';
        $params[':visibility'] = $visibility;
    }

    if ($search !== '') {
        $where[]         = '(title LIKE :search OR description LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($category !== '') {
        $where[]           = 'category = :category';
        $params[':category'] = $category;
    }

    $sql = 'SELECT * FROM dm_downloads';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

    $stmt = getDB()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Counts download records matching the given filters.
 *
 * Mirrors getDownloads() filters so pagination totals are accurate.
 *
 * @param string $visibility  'public' | 'private' | 'all'.
 * @param string $search      Optional search term.
 * @param string $category    Optional category filter.
 *
 * @return int  Total matching rows.
 */
function countDownloads(
    string $visibility = 'public',
    string $search     = '',
    string $category   = ''
): int {
    $where  = [];
    $params = [];

    if ($visibility !== 'all') {
        $where[]              = 'visibility = :visibility';
        $params[':visibility'] = $visibility;
    }

    if ($search !== '') {
        $where[]         = '(title LIKE :search OR description LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($category !== '') {
        $where[]           = 'category = :category';
        $params[':category'] = $category;
    }

    $sql = 'SELECT COUNT(*) FROM dm_downloads';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $stmt = getDB()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

/**
 * Fetches a single download record by its ID.
 *
 * @param int $id  Download record ID.
 *
 * @return array<string,mixed>|null  Row data or null when not found.
 */
function getDownload(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM dm_downloads WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Increments the download_count for a given record.
 *
 * @param int $id  Download record ID.
 *
 * @return void
 */
function incrementDownloadCount(int $id): void
{
    $stmt = getDB()->prepare(
        'UPDATE dm_downloads SET download_count = download_count + 1 WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

/**
 * Returns a distinct list of categories that have at least one file.
 *
 * @return list<string>  Sorted category names.
 */
function getCategories(): array
{
    $rows = getDB()
        ->query("SELECT DISTINCT category FROM dm_downloads WHERE category != '' ORDER BY category ASC")
        ->fetchAll(PDO::FETCH_COLUMN);
    return $rows;
}

// ===========================================================================
// Download token helpers
// ===========================================================================

/**
 * Generates a cryptographically secure random token string.
 *
 * @return string  64-character hex token.
 */
function generateToken(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Creates a time-limited secure download token for a file.
 *
 * @param int $downloadId     ID of the download record.
 * @param int $expiresInHours Number of hours until the token expires.
 *
 * @return string  The generated token string.
 */
function createDownloadToken(int $downloadId, int $expiresInHours = 24): string
{
    $token     = generateToken();
    $expiresAt = gmdate('Y-m-d H:i:s', time() + ($expiresInHours * 3600));

    $stmt = getDB()->prepare(
        'INSERT INTO dm_download_tokens (download_id, token, expires_at)
         VALUES (:download_id, :token, :expires_at)'
    );
    $stmt->execute([
        ':download_id' => $downloadId,
        ':token'       => $token,
        ':expires_at'  => $expiresAt,
    ]);

    return $token;
}

/**
 * Validates a download token and returns the associated download ID.
 *
 * Returns null if the token is missing, expired, or does not match
 * the given download ID. Does not consume (delete) the token.
 *
 * @param int    $downloadId  Expected download ID.
 * @param string $token       Token string from the query string.
 *
 * @return bool  True when the token is valid and unexpired.
 */
function validateDownloadToken(int $downloadId, string $token): bool
{
    $stmt = getDB()->prepare(
        "SELECT id FROM dm_download_tokens
         WHERE download_id = :download_id
           AND token       = :token
           AND expires_at  > datetime('now')
         LIMIT 1"
    );
    $stmt->execute([
        ':download_id' => $downloadId,
        ':token'       => $token,
    ]);
    return $stmt->fetch() !== false;
}

// ===========================================================================
// Pagination helper
// ===========================================================================

/**
 * Renders HTML pagination links.
 *
 * @param int    $total    Total number of items.
 * @param int    $perPage  Items per page.
 * @param int    $current  Current page number (1-based).
 * @param string $baseUrl  Base URL without page parameter.
 *
 * @return string  HTML pagination markup.
 */
function renderPagination(int $total, int $perPage, int $current, string $baseUrl): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) {
        return '';
    }

    $sep  = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav class="pagination" aria-label="Page navigation">';

    $html .= $current > 1
        ? '<a href="' . e($baseUrl . $sep . 'page=' . ($current - 1)) . '" aria-label="Previous">&laquo; Prev</a>'
        : '<span class="disabled" aria-disabled="true">&laquo; Prev</span>';

    for ($i = 1; $i <= $pages; $i++) {
        if ($i === $current) {
            $html .= '<span class="current" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a>';
        }
    }

    $html .= $current < $pages
        ? '<a href="' . e($baseUrl . $sep . 'page=' . ($current + 1)) . '" aria-label="Next">Next &raquo;</a>'
        : '<span class="disabled" aria-disabled="true">Next &raquo;</span>';

    $html .= '</nav>';
    return $html;
}
