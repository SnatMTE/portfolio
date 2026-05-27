<?php
/**
 * crm/core/standalone_auth.php
 *
 * CMS-compatible authentication stubs for standalone CRM operation.
 *
 * When the CRM runs without a parent CMS these functions shadow the CMS
 * equivalents so all CRM pages work unchanged.  In CMS-integrated mode
 * the CMS already defines all of these, so this file is never loaded.
 *
 * Session keys used in standalone mode:
 *   crm_user_id  – integer PK from crm_users
 *   crm_username – display name
 *   crm_role     – 'admin' | 'editor' | 'user'
 */

// ---------------------------------------------------------------------------
// e() — HTML-safe output helper
// ---------------------------------------------------------------------------

if (!function_exists('e')) {
    /**
     * Escapes a string for safe HTML output.
     *
     * @param string $s Raw string.
     * @return string   HTML-safe string.
     */
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ---------------------------------------------------------------------------
// redirect() — safe header redirect
// ---------------------------------------------------------------------------

if (!function_exists('redirect')) {
    /**
     * Issues a Location redirect and halts execution.
     *
     * @param string $url Absolute or root-relative URL.
     * @return never
     */
    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Authentication checks — backed by $_SESSION['crm_*']
// ---------------------------------------------------------------------------

if (!function_exists('cmsIsLoggedIn')) {
    /**
     * Returns true if a CRM user is logged in.
     *
     * @return bool
     */
    function cmsIsLoggedIn(): bool
    {
        return !empty($_SESSION['crm_user_id']);
    }
}

if (!function_exists('cmsIsAdmin')) {
    /**
     * Returns true if the current user holds the admin role.
     *
     * @return bool
     */
    function cmsIsAdmin(): bool
    {
        return ($_SESSION['crm_role'] ?? '') === 'admin';
    }
}

if (!function_exists('cmsIsEditor')) {
    /**
     * Returns true if the current user holds admin or editor privileges.
     *
     * @return bool
     */
    function cmsIsEditor(): bool
    {
        return in_array($_SESSION['crm_role'] ?? '', ['admin', 'editor'], true);
    }
}

if (!function_exists('currentCMSUser')) {
    /**
     * Returns an associative array of the logged-in user's data, or null.
     *
     * @return array{id: int, username: string, email: string, role: string}|null
     */
    function currentCMSUser(): ?array
    {
        if (empty($_SESSION['crm_user_id'])) {
            return null;
        }
        // Fetch fresh data from the CRM users table on each call.
        // The getCRMDB() function is defined in crm/core/database.php.
        try {
            $stmt = getCRMDB()->prepare(
                "SELECT u.id, u.username, u.email, r.name AS role
                 FROM crm_users u
                 JOIN crm_roles r ON r.id = u.role_id
                 WHERE u.id = :id"
            );
            $stmt->execute([':id' => (int) $_SESSION['crm_user_id']]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Exception) {
            return null;
        }
    }
}

// ---------------------------------------------------------------------------
// getCMSDB() — user-table bridge
//
// Core helpers (crmGetUsers, crmUsername) call getCMSDB() so they can query
// the CMS users table when integrated.  In standalone mode we return the CRM
// DB connection; the crm_users table has a 'users' VIEW that matches the
// expected column names.
// ---------------------------------------------------------------------------

if (!function_exists('getCMSDB')) {
    /**
     * Returns the PDO connection used for user lookups.
     * In standalone mode this is the CRM database (which exposes a 'users' view).
     *
     * @return PDO
     */
    function getCMSDB(): PDO
    {
        return getCRMDB();
    }
}

// ---------------------------------------------------------------------------
// Flash messages — session-based one-shot notifications
// ---------------------------------------------------------------------------

if (!function_exists('cmsFlashMessage')) {
    /**
     * Stores a flash message to display on the next page load.
     *
     * @param string $message Human-readable message.
     * @param string $type    'success' | 'error' | 'info'
     * @return void
     */
    function cmsFlashMessage(string $message, string $type = 'success'): void
    {
        $_SESSION['crm_flash'] = ['message' => $message, 'type' => $type];
    }
}

if (!function_exists('cmsGetFlash')) {
    /**
     * Retrieves and clears the pending flash message.
     *
     * @return array{message: string, type: string}|null
     */
    function cmsGetFlash(): ?array
    {
        if (empty($_SESSION['crm_flash'])) {
            return null;
        }
        $flash = $_SESSION['crm_flash'];
        unset($_SESSION['crm_flash']);
        return $flash;
    }
}

// ---------------------------------------------------------------------------
// CSRF tokens
// ---------------------------------------------------------------------------

if (!function_exists('cmsCsrfToken')) {
    /**
     * Returns the current CSRF token, generating one if needed.
     *
     * @return string 64-character hex token.
     */
    function cmsCsrfToken(): string
    {
        if (empty($_SESSION['crm_csrf_token'])) {
            $_SESSION['crm_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['crm_csrf_token'];
    }
}

if (!function_exists('cmsValidateCsrf')) {
    /**
     * Validates the supplied CSRF token against the session token.
     *
     * @param string $token Token submitted with the request.
     * @return bool
     */
    function cmsValidateCsrf(string $token): bool
    {
        $expected = $_SESSION['crm_csrf_token'] ?? '';
        return $expected !== '' && hash_equals($expected, $token);
    }
}

// ---------------------------------------------------------------------------
// getSetting / setSetting — thin stubs backed by crm_settings table
// ---------------------------------------------------------------------------

if (!function_exists('getSetting')) {
    /**
     * Reads a setting value from crm_settings, returning $default if absent.
     *
     * @param string $key     Setting name.
     * @param string $default Fallback value.
     * @return string
     */
    function getSetting(string $key, string $default = ''): string
    {
        try {
            $stmt = getCRMDB()->prepare(
                "SELECT value FROM crm_settings WHERE key = :key"
            );
            $stmt->execute([':key' => $key]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? (string) $val : $default;
        } catch (\Exception) {
            return $default;
        }
    }
}

if (!function_exists('setSetting')) {
    /**
     * Inserts or updates a setting in crm_settings.
     *
     * @param string $key   Setting name.
     * @param string $value New value.
     * @return void
     */
    function setSetting(string $key, string $value): void
    {
        try {
            $stmt = getCRMDB()->prepare(
                "INSERT INTO crm_settings (key, value) VALUES (:key, :value)
                 ON CONFLICT(key) DO UPDATE SET value = excluded.value"
            );
            $stmt->execute([':key' => $key, ':value' => $value]);
        } catch (\Exception) {
            // Silently ignore — settings are non-critical in standalone mode.
        }
    }
}
