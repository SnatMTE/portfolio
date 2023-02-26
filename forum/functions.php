<?php
/**
 * functions.php
 *
 * Global helper functions for the Forum.
 * All database queries use PDO prepared statements to prevent SQL injection.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// mbstring compatibility shim
// ---------------------------------------------------------------------------
if (!extension_loaded('mbstring')) {
    if (!function_exists('mb_strlen')) {
        
        /**
         * UTF-8-aware strlen fallback (mbstring not available).
         *
         * @param string $s
         * @param string $encoding
         * @return int
         */
        function mb_strlen(string $s, string $encoding = 'UTF-8'): int
        {
            if ($s === '') return 0;
            preg_match_all('/./us', $s, $m);
            return count($m[0]);
        }
    }
    if (!function_exists('mb_substr')) {
        
        /**
         * UTF-8-aware substr fallback (mbstring not available).
         *
         * @param string $s
         * @param int $start
         * @param ?int $length
         * @param string $encoding
         * @return string
         */
        function mb_substr(string $s, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
        {
            if ($s === '') return '';
            preg_match_all('/./us', $s, $m);
            $arr = $m[0];
            if ($start < 0) {
                $start = count($arr) + $start;
            }
            return $length === null
                ? implode('', array_slice($arr, $start))
                : implode('', array_slice($arr, $start, $length));
        }
    }
    if (!function_exists('mb_strtolower')) {
        
        /**
         * Best-effort strtolower fallback for UTF-8 strings.
         *
         * @param string $s
         * @param string $encoding
         * @return string
         */
        function mb_strtolower(string $s, string $encoding = 'UTF-8'): string
        {
            return strtolower($s);
        }
    }
}

// ===========================================================================
// String / Output helpers
// ===========================================================================

/**
 * Escapes a string for safe HTML output, preventing XSS.
 *
 * @param string $string  Raw input.
 * @return string  HTML-safe string.
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


/**
 * Converts a string into a URL-friendly slug.
 *
 * @param string $text  Input text.
 * @return string  Slug.
 */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Formats a UTC date string for human-readable display.
 *
 * @param string $dateString  SQLite datetime string.
 * @param string $format      PHP date() format.
 * @return string
 */
function formatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Formats a date string as "1 January 2025 at 3:45pm".
 *
 * @param string $dateString  SQLite datetime string.
 * @return string
 */
function formatDateTime(string $dateString): string
{
    return formatDate($dateString, 'j F Y \a\t g:ia');
}

/**
 * Redirects the browser to the given URL and terminates execution.
 *
 * @param string $url  Destination URL.
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Truncates a string (stripping HTML) to the given character length.
 *
 * @param string $text    Input text (may contain HTML).
 * @param int    $length  Max characters.
 * @return string
 */
function truncate(string $text, int $length = 120): string
{
    $plain = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', trim($plain));
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }
    return mb_substr($plain, 0, $length) . '...';
}

// ===========================================================================
// Avatar / Gravatar helpers
// ===========================================================================

/**
 * Returns a Gravatar URL for the given email address.
 *
 * @param string|null $email   User email address (may be null).
 * @param int         $size    Pixel size (square).
 * @param string      $default Default image type (identicon, mm, etc.).
 * @param string      $rating  Gravatar rating (g, pg, r, x).
 * @return string
 */
function gravatar_url(?string $email, int $size = 80, string $default = 'identicon', string $rating = 'g'): string
{
    $hash = md5(strtolower(trim((string) $email)));
    return 'https://www.gravatar.com/avatar/' . $hash
         . '?s=' . (int) $size
         . '&d=' . rawurlencode($default)
         . '&r=' . rawurlencode($rating);
}

// ===========================================================================
// CSRF Protection
// ===========================================================================

/**
 * Returns (and generates if needed) the session CSRF token.
 *
 * @return string  Hex-encoded token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token from POST data.
 *
 * @param string $submittedToken  The token value from the POST form field.
 * @return bool  True if the token is valid, false otherwise.
 */
function validateCsrf(string $submittedToken): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return $sessionToken === $submittedToken;
}

// ===========================================================================
// Flash messages
// ===========================================================================

/**
 * Stores a one-time flash message in the session.
 *
 * @param string $message  Message text.
 * @param string $type     'success' | 'error' | 'info'
 * @return void
 */
function flashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieves and clears the stored flash message.
 *
 * @return array{message: string, type: string}|null
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ===========================================================================
// Authentication helpers
// ===========================================================================

/**
 * Returns true if a user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Returns the currently logged-in user's data, or null.
 *
 * Caches the result for the duration of the request.
 *
 * @return array<string, mixed>|null
 */
function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user !== null) {
        return $user;
    }

    // CMS mode: return session-based data and ensure a shadow record exists
    // in the forum DB for FK constraints (threads/posts).
    if (defined('CMS_ROOT')) {
        $user = [
            'id'         => (int) $_SESSION['user_id'],
            'username'   => $_SESSION['username'] ?? 'User',
            'email'      => '',
            'bio'        => '',
            'created_at' => '',
            'role'       => $_SESSION['role'] ?? 'user',
        ];
        _ensureForumUserRecord($user['id'], $user['username'], $user['role']);
        return $user;
    }

    // Standalone mode: query the forum DB.
    $stmt = getDB()->prepare(
        "SELECT u.id, u.username, u.email, u.bio, u.created_at, r.name AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id"
    );
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

/**
 * Ensures a shadow user record exists in the forum DB for FK constraints.
 * Only used in CMS mode. Silently no-ops on failure.
 *
 * @param int    $userId
 * @param string $username
 * @param string $role
 * @return void
 */
function _ensureForumUserRecord(int $userId, string $username, string $role): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;
    try {
        $db     = getDB();
        $roleId = $role === 'admin' ? 1 : 2;
        $stmt = $db->prepare(
            "INSERT OR IGNORE INTO users (id, username, email, password_hash, role_id)
             VALUES (:id, :username, :email, '', :role_id)"
        );
        $stmt->execute([
            ':id'      => $userId,
            ':username'=> $username,
            ':email'   => 'cms-user-' . $userId . '@cms.local',
            ':role_id' => $roleId,
        ]);
    } catch (\Exception $e) {
        // Non-fatal: FK errors may surface later but we handle gracefully.
    }
}

/**
 * Returns true if the current user has the admin role.
 *
 * @return bool
 */
function isAdmin(): bool
{
    // CMS mode: trust the role stored in the session.
    if (defined('CMS_ROOT')) {
        return ($_SESSION['role'] ?? '') === 'admin';
    }
    $user = currentUser();
    return $user !== null && $user['role'] === 'admin';
}

/**
 * Redirects to the login page if the visitor is not authenticated.
 *
 * @return void
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        flashMessage('You must be logged in to do that.', 'error');
        $loginUrl = defined('CMS_URL') ? CMS_URL . '/login.php' : SITE_URL . '/login.php';
        redirect($loginUrl);
    }
}

/**
 * Terminates with 403 if the visitor is not an administrator.
 *
 * @return void
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}
