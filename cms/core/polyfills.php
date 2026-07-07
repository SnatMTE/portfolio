<?php
/**
 * cms/core/polyfills.php
 *
 * UTF-8-aware fallback implementations for mbstring functions.
 * Loaded automatically when the mbstring extension is not available.
 *
 * These polyfills ensure the application runs on minimal PHP installations
 * while maintaining correct Unicode handling. For production environments,
 * installing/enabling the PHP `mbstring` extension is strongly recommended.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

// Only define polyfills if mbstring extension is not loaded
if (!extension_loaded('mbstring')) {
    if (!function_exists('mb_strlen')) {
        /**
         * Returns the number of characters in a UTF-8 string.
         *
         * @param string $s        The string to measure.
         * @param string $encoding Character encoding (ignored; always UTF-8).
         * @return int             Number of characters.
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
         * Returns a portion of a UTF-8 string.
         *
         * @param string   $s        The input string.
         * @param int      $start    Starting position (0-based).
         * @param int|null $length   Length of substring (null = to end).
         * @param string   $encoding Character encoding (ignored; always UTF-8).
         * @return string            Extracted substring.
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
         * Converts a string to lowercase (best-effort for ASCII).
         *
         * Note: For full Unicode case folding, enable the mbstring extension.
         *
         * @param string $s        The input string.
         * @param string $encoding Character encoding (ignored).
         * @return string           Lowercase string.
         */
        function mb_strtolower(string $s, string $encoding = 'UTF-8'): string
        {
            return strtolower($s);
        }
    }

    if (!function_exists('mb_strtoupper')) {
        /**
         * Converts a string to uppercase (best-effort for ASCII).
         *
         * @param string $s        The input string.
         * @param string $encoding Character encoding (ignored).
         * @return string           Uppercase string.
         */
        function mb_strtoupper(string $s, string $encoding = 'UTF-8'): string
        {
            return strtoupper($s);
        }
    }
}
