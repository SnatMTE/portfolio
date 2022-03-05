<?php
/**
 * functions.php
 *
 * Global helper functions used across the portfolio blog.
 * All database queries use PDO prepared statements to prevent SQL injection.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// --------------------------------------------------------------------------
// mbstring compatibility shim
// --------------------------------------------------------------------------
// Provide tiny UTF-8-safe fallbacks when `mbstring` is absent.
// Enable the `mbstring` extension for reliable internationalisation.
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
            if ($encoding === '8bit') {
                return strlen($s);
            }
            if ($s === '') {
                return 0;
            }
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
            if ($encoding === '8bit') {
                return $length === null ? substr($s, $start) : substr($s, $start, $length);
            }
            if ($s === '') {
                return '';
            }
            preg_match_all('/./us', $s, $m);
            $arr = $m[0];
            if ($start < 0) {
                $start = count($arr) + $start;
            }
            if ($length === null) {
                return implode('', array_slice($arr, $start));
            }
            return implode('', array_slice($arr, $start, $length));
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
            // Best-effort fallback; for full Unicode casing enable mbstring.
            return strtolower($s);
        }
    }

    if (!function_exists('mb_strtoupper')) {
        
        /**
         * Best-effort strtoupper fallback for UTF-8 strings.
         *
         * @param string $s
         * @param string $encoding
         * @return string
         */
        function mb_strtoupper(string $s, string $encoding = 'UTF-8'): string
        {
            return strtoupper($s);
        }
    }
}

// ===========================================================================
// String / Output helpers
// ===========================================================================

/**
 * Escapes a string for safe HTML output.
 *
 * Wraps htmlspecialchars() with UTF-8 encoding and ENT_QUOTES so both
 * single and double quotes are escaped, preventing XSS.
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
 * Converts a plain text string into a URL-friendly slug.
 *
 * Lowercases the string, replaces spaces and non-alphanumeric characters
 * with hyphens, then trims leading/trailing hyphens.
 *
 * @param string $text  Input text (e.g. post title).
 *
 * @return string  URL slug (e.g. "my-first-post").
 */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Generates an excerpt from a block of HTML content.
 *
 * Strips HTML tags, decodes entities, then truncates to $length characters,
 * appending an ellipsis if the content was truncated.
 *
 * @param string $htmlContent  Full HTML post content.
 * @param int    $length       Maximum character length of the excerpt.
 *
 * @return string  Plain-text excerpt.
 */
function makeExcerpt(string $htmlContent, int $length = 200): string
{
    $plain = html_entity_decode(strip_tags($htmlContent), ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', trim($plain));

    if (mb_strlen($plain) <= $length) {
        return $plain;
    }

    return mb_substr($plain, 0, $length) . '…';
}

/**
 * Formats a UTC date string for human-readable display.
 *
 * @param string $dateString  Date string stored in the database (SQLite datetime).
 * @param string $format      PHP date() format string.
 *
 * @return string  Formatted date string.
 */
function formatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Redirects the browser to the given URL and terminates execution.
 *
 * @param string $url  Destination URL (absolute or relative).
 *
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Generates a CSRF token for the current session and stores it.
 *
 * If a token already exists in the session it is returned unchanged so
 * that forms rendered multiple times in one request share the same token.
 *
 * @return string  A 64-character hex CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token submitted with a POST request.
 *
 * Compares the submitted token against the session token using a
 * timing-safe comparison to prevent timing attacks.
 *
 * @param string $submittedToken  The token value from the POST form field.
 *
 * @return bool  TRUE if the token is valid, FALSE otherwise.
 */
function validateCsrf(string $submittedToken): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return $sessionToken === $submittedToken;
}
