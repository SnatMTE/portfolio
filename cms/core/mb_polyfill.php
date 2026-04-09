<?php
/**
 * cms/core/mb_polyfill.php
 *
 * Lightweight polyfills for common mb_ functions when the mbstring
 * extension isn't available. These provide basic UTF-8-safe fallbacks
 * sufficient for ASCII and many UTF-8 payloads; install the mbstring
 * PHP extension for full internationalization support.
 */

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $str, string $encoding = null): int
    {
        // Prefer utf8 decoding to count characters for common UTF-8
        if (function_exists('utf8_decode')) {
            return strlen(utf8_decode($str));
        }
        return strlen($str);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $str, int $start, ?int $length = null, string $encoding = null): string
    {
        if ($length === null) {
            return substr($str, $start);
        }
        return substr($str, $start, $length);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $str, string $encoding = null): string
    {
        return strtolower($str);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $str, string $encoding = null): string
    {
        return strtoupper($str);
    }
}

if (!function_exists('mb_strpos')) {
    function mb_strpos(string $haystack, string $needle, int $offset = 0, string $encoding = null)
    {
        return strpos($haystack, $needle, $offset);
    }
}
