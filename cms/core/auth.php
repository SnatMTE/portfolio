<?php
/**
 * File: auth.php
 * What it does: Short description of the file's purpose.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

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
 * @author  Snat
 * @link    https://terra.me.uk
 */

if (!defined('CMS_ROOT')) {
    require_once dirname(__DIR__) . '/config.php';
}

// ---------------------------------------------------------------------------
// Session checks
// ---------------------------------------------------------------------------


/**
 * cmsIsLoggedIn — Short description of the function's behaviour.
 *
 * @return bool
 */
function cmsIsLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}


/**
 * cmsIsAdmin — Short description of the function's behaviour.
 *
 * @return bool
 */
function cmsIsAdmin(): bool
{
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}


/**
 * cmsIsEditor — Short description of the function's behaviour.
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
 * requireCMSAuth — Short description of the function's behaviour.
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
 * requireCMSAdmin — Short description of the function's behaviour.
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
 * requireCMSEditor — Short description of the function's behaviour.
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
 * cmsCsrfToken — Short description of the function's behaviour.
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
 * cmsValidateCsrf — Short description of the function's behaviour.
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
 * cmsFlashMessage — Short description of the function's behaviour.
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
 * cmsGetFlash — Short description of the function's behaviour.
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
     * redirect — Short description of the function's behaviour.
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
     * e — Short description of the function's behaviour.
     *
     * @param string $string
     * @return string
     */
    function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
