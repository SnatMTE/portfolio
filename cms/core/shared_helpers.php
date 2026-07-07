<?php
/**
 * cms/core/shared_helpers.php
 *
 * Shared utility functions available to all modules when running in CMS mode.
 * These functions eliminate code duplication across blog, forum, and store modules.
 *
 * Functions provided:
 *   - e()              : HTML escaping
 *   - slugify()        : URL-friendly slug generation
 *   - formatDate()     : Date formatting
 *   - makeExcerpt()    : Content excerpt generation
 *   - redirect()       : HTTP redirect with exit
 *   - truncate()       : Text truncation with HTML stripping
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

// Only define these helpers if they don't already exist (allows modules
// to override with their own implementations if needed)

if (!function_exists('e')) {
    /**
     * Escapes a string for safe HTML output, preventing XSS attacks.
     *
     * @param string $string Raw input string.
     * @return string        HTML-safe escaped string.
     */
    function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('slugify')) {
    /**
     * Converts a string to a URL-friendly slug.
     *
     * Lowercases the string, removes non-alphanumeric characters (except hyphens),
     * replaces spaces with hyphens, and trims leading/trailing hyphens.
     *
     * @param string $text Input text (e.g., post title).
     * @return string      URL-safe slug.
     */
    function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
        $text = preg_replace('/[\s\-]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('formatDate')) {
    /**
     * Formats a UTC datetime string for human-readable display.
     *
     * @param string $dateString SQLite datetime string (UTC).
     * @param string $format     PHP date() format string.
     * @return string            Formatted date string.
     */
    function formatDate(string $dateString, string $format = 'j F Y'): string
    {
        $dt = new DateTime($dateString, new DateTimeZone('UTC'));
        return $dt->format($format);
    }
}

if (!function_exists('makeExcerpt')) {
    /**
     * Generates a plain-text excerpt from HTML content.
     *
     * Strips HTML tags, decodes entities, normalizes whitespace, and truncates
     * to the specified length with an ellipsis if truncated.
     *
     * @param string $htmlContent Full HTML content.
     * @param int    $length      Maximum character length.
     * @return string             Plain-text excerpt.
     */
    function makeExcerpt(string $htmlContent, int $length = 200): string
    {
        $plain = html_entity_decode(strip_tags($htmlContent), ENT_QUOTES, 'UTF-8');
        $plain = preg_replace('/\s+/', ' ', trim($plain));

        if (mb_strlen($plain, 'UTF-8') <= $length) {
            return $plain;
        }

        return mb_substr($plain, 0, $length, 'UTF-8') . '…';
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirects the browser to the given URL and terminates execution.
     *
     * @param string $url Destination URL (absolute or relative).
     * @return never
     */
    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('truncate')) {
    /**
     * Truncates text (stripping HTML) to a specified character length.
     *
     * @param string $text   Input text (may contain HTML).
     * @param int    $length Maximum character length.
     * @return string        Truncated plain text.
     */
    function truncate(string $text, int $length = 120): string
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $plain = preg_replace('/\s+/', ' ', trim($plain));

        if (mb_strlen($plain, 'UTF-8') <= $length) {
            return $plain;
        }

        return mb_substr($plain, 0, $length, 'UTF-8') . '…';
    }
}
