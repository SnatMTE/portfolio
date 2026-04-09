<?php
/**
 * cms/core/helpers.php
 *
 * Shared utility functions for CMS pages (settings, slugify, pagination).
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

/**
 * Retrieves a setting value by key, or a default if not set.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function getSetting(string $key, string $default = ''): string
{
    $stmt = getCMSDB()->prepare("SELECT value FROM settings WHERE key = :key");
    $stmt->execute([':key' => $key]);
    $row = $stmt->fetchColumn();
    return ($row !== false) ? (string) $row : $default;
}

/**
 * Persists a setting value.
 *
 * @param string $key
 * @param string $value
 * @return void
 */
function setSetting(string $key, string $value): void
{
    $stmt = getCMSDB()->prepare(
        "INSERT INTO settings (key, value) VALUES (:key, :value)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value"
    );
    $stmt->execute([':key' => $key, ':value' => $value]);
}

/**
 * Converts a string to a URL-friendly slug.
 *
 * @param string $text
 * @return string
 */
function cmsSlugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Formats a UTC SQLite datetime string for display.
 *
 * @param string $dateString
 * @param string $format
 * @return string
 */
function cmsFormatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Truncates a string (stripping HTML) to the given length.
 *
 * @param string $text
 * @param int    $length
 * @return string
 */
function cmsTruncate(string $text, int $length = 120): string
{
    $plain = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', trim($plain));
    if (mb_strlen($plain, 'UTF-8') <= $length) {
        return $plain;
    }
    return mb_substr($plain, 0, $length, 'UTF-8') . '…';
}

/**
 * Returns basic CMS dashboard statistics.
 *
 * @return array{users: int, pages: int, modules: int}
 */
function getCMSStats(): array
{
    $db = getCMSDB();
    return [
        'users'   => (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'pages'   => (int) $db->query("SELECT COUNT(*) FROM pages")->fetchColumn(),
        'modules' => count(getActiveModules()),
    ];
}
