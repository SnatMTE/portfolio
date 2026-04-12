<?php
/**
 * cms/core/auth.php
 *
 * CMS authentication helpers.
 * Provides session checks, access control guards, flash messages, and CSRF.
 *
 * Functions
 * ---------
 *   cmsIsLoggedIn()     – True if a user session is active.
 *   cmsIsAdmin()        – True if the current user has the admin role.
 *   cmsIsEditor()       – True if admin or editor role.
 *   currentCMSUser()    – Returns the current user's row from the CMS DB.
 *   requireCMSAuth()    – Redirects to login if not authenticated.
 *   requireCMSAdmin()   – Redirects/403s if not admin.
 *   cmsCsrfToken()      – Returns (and generates) the session CSRF token.
 *   cmsValidateCsrf()   – Validates a submitted CSRF token.
 *   cmsFlashMessage()   – Stores a one-time flash message.
 *   cmsGetFlash()       – Retrieves and clears the flash message.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

if (!defined('CMS_ROOT')) {
    require_once dirname(__DIR__) . '/config.php';
}

// ---------------------------------------------------------------------------
// Session checks
// ---------------------------------------------------------------------------


/**
 * Returns true when a CMS user session is active.
 *
 * @return bool
 */
function cmsIsLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}


/**
 * Returns true if the current CMS user has the admin role.
 *
 * @return bool
 */
function cmsIsAdmin(): bool
{
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}


/**
 * Returns true if the current CMS user is an admin or editor.
 *
 * @return bool
 */
function cmsIsEditor(): bool
{
    return !empty($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'editor'], true);
}

// ---------------------------------------------------------------------------
// User data
// ---------------------------------------------------------------------------

/**
 * Returns the currently authenticated CMS user's row, or null.
 *
 * @return array<string, mixed>|null
 */
function currentCMSUser(): ?array
{
    if (!cmsIsLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = getCMSDB()->prepare(
            "SELECT u.id, u.username, u.email, u.created_at, r.name AS role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id"
        );
        $stmt->execute([':id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

// ---------------------------------------------------------------------------
// Access guards
// ---------------------------------------------------------------------------


/**
 * Redirects to the CMS login page when no user is authenticated.
 *
 * @return void
 */
function requireCMSAuth(): void
{
    if (!cmsIsLoggedIn()) {
        cmsFlashMessage('Please log in to continue.', 'error');
        redirect(SITE_URL . '/login.php');
    }
}


/**
 * Ensures the current user is an administrator, otherwise redirects or 403s.
 *
 * @return void
 */
function requireCMSAdmin(): void
{
    if (!cmsIsLoggedIn()) {
        cmsFlashMessage('Please log in to continue.', 'error');
        redirect(SITE_URL . '/login.php');
    }
    if (!cmsIsAdmin()) {
        http_response_code(403);
        exit('Access denied: administrator privileges required.');
    }
}


/**
 * Ensures the current user has editor privileges, otherwise redirects or 403s.
 *
 * @return void
 */
function requireCMSEditor(): void
{
    if (!cmsIsLoggedIn()) {
        cmsFlashMessage('Please log in to continue.', 'error');
        redirect(SITE_URL . '/login.php');
    }
    if (!cmsIsEditor()) {
        http_response_code(403);
        exit('Access denied: editor privileges required.');
    }
}

// ---------------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------------


/**
 * Returns the CMS session CSRF token, generating one if missing.
 *
 * @return string
 */
function cmsCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


/**
 * Validates a submitted CMS CSRF token against the session token.
 *
 * @param string $token
 * @return bool
 */
function cmsValidateCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------


/**
 * Stores a one-time CMS flash message in the session.
 *
 * @param string $message
 * @param string $type
 * @return void
 */
function cmsFlashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}


/**
 * Retrieves and clears the stored CMS flash message, or null if none.
 *
 * @return ?array
 */
function cmsGetFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// Shared redirect helper (available to modules that don't load functions.php)
// ---------------------------------------------------------------------------
if (!function_exists('redirect')) {
    
    /**
     * Redirects to a given URL and terminates execution.
     *
     * @param string $url
     * @return never
     */
    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

// ---------------------------------------------------------------------------
// HTML escape helper (available to CMS pages)
// ---------------------------------------------------------------------------
if (!function_exists('e')) {
    
    /**
     * HTML-encodes a string for safe output in CMS pages.
     *
     * @param string $string
     * @return string
     */
    function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
