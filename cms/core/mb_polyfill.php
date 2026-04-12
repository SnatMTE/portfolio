<?php
/**
 * cms/core/mb_polyfill.php
 *
 * Lightweight polyfills for common mb_ functions when the mbstring
 * extension isn't available. These provide basic UTF-8-safe fallbacks
 * sufficient for ASCII and many UTF-8 payloads; install the mbstring
 * PHP extension for full internationalisation support.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

if (!function_exists('mb_strlen')) {
    
    /**
     * UTF-8-aware strlen fallback for environments without mbstring.
     *
     * @param string $str
     * @param string $encoding
     * @return int
     */
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
    
    /**
     * UTF-8-safe substr fallback for environments without mbstring.
     *
     * @param string $str
     * @param int $start
     * @param ?int $length
     * @param string $encoding
     * @return string
     */
    function mb_substr(string $str, int $start, ?int $length = null, string $encoding = null): string
    {
        if ($length === null) {
            return substr($str, $start);
        }
        return substr($str, $start, $length);
    }
}

if (!function_exists('mb_strtolower')) {
    
    /**
     * Best-effort strtolower fallback for UTF-8 strings.
     *
     * @param string $str
     * @param string $encoding
     * @return string
     */
    function mb_strtolower(string $str, string $encoding = null): string
    {
        return strtolower($str);
    }
}

if (!function_exists('mb_strtoupper')) {
    
    /**
     * Best-effort strtoupper fallback for UTF-8 strings.
     *
     * @param string $str
     * @param string $encoding
     * @return string
     */
    function mb_strtoupper(string $str, string $encoding = null): string
    {
        return strtoupper($str);
    }
}

if (!function_exists('mb_strpos')) {
    
    /**
     * strpos fallback for environments lacking mbstring.
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @param string $encoding
     * @return mixed
     */
    function mb_strpos(string $haystack, string $needle, int $offset = 0, string $encoding = null)
    {
        return strpos($haystack, $needle, $offset);
    }
}
