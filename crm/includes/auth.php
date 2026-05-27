<?php
/**
 * crm/includes/auth.php
 *
 * CRM access guards.
 * Wraps the CMS auth functions with CRM-specific permission levels.
 *
 * Roles that can access the CRM:
 *   admin  – Full access: all records, delete, manage users.
 *   editor – Can create/edit customers, leads, tasks; cannot delete.
 *   user   – Read-only dashboard and own assigned tasks/leads.
 *
 * All guards use the existing CMS session and role system.
 * No additional session variables are required.
 */

if (!function_exists('cmsIsLoggedIn')) {
    require_once dirname(__DIR__) . '/functions.php';
}

// ---------------------------------------------------------------------------
// Permission level helpers
// ---------------------------------------------------------------------------

/**
 * Returns true if the current user may access the CRM at all.
 *
 * @return bool
 */
function crmCanAccess(): bool
{
    // Any authenticated CMS user can enter the CRM.
    return cmsIsLoggedIn();
}

/**
 * Returns true if the current user may create or edit CRM records.
 *
 * @return bool
 */
function crmCanEdit(): bool
{
    return cmsIsLoggedIn() && in_array($_SESSION['role'] ?? '', ['admin', 'editor'], true);
}

/**
 * Returns true if the current user may delete CRM records.
 *
 * @return bool
 */
function crmCanDelete(): bool
{
    return cmsIsAdmin();
}

// ---------------------------------------------------------------------------
// Route guards — call at the top of each CRM page
// ---------------------------------------------------------------------------

/**
 * Redirects to the CMS login page if the visitor is not authenticated.
 *
 * @return void
 */
function requireCRMAccess(): void
{
    if (!crmCanAccess()) {
        cmsFlashMessage('Please log in to access the CRM.', 'error');
        // Preserve the intended URL so the login page can redirect back.
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '');
        crmRedirect(SITE_URL . '/login.php?next=' . $next);
    }
}

/**
 * Halts with 403 unless the user has editor privileges.
 *
 * @return void
 */
function requireCRMEditor(): void
{
    requireCRMAccess();
    if (!crmCanEdit()) {
        http_response_code(403);
        exit('Access denied: editor privileges required for this action.');
    }
}

/**
 * Halts with 403 unless the user is an admin.
 *
 * @return void
 */
function requireCRMAdmin(): void
{
    requireCRMAccess();
    if (!crmCanDelete()) {
        http_response_code(403);
        exit('Access denied: administrator privileges required for this action.');
    }
}
