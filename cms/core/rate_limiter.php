<?php
/**
 * cms/core/rate_limiter.php
 *
 * Simple file-based rate limiter for authentication endpoints.
 * Tracks failed login attempts per IP address and enforces exponential backoff.
 *
 * Usage:
 *   require_once CMS_ROOT . '/core/rate_limiter.php';
 *   
 *   // Check before processing login
 *   if (!checkLoginRateLimit($_SERVER['REMOTE_ADDR'])) {
 *       $errors[] = 'Too many failed attempts. Please try again later.';
 *   }
 *   
 *   // Record failed attempt
 *   recordFailedLogin($_SERVER['REMOTE_ADDR']);
 *   
 *   // Clear on successful login
 *   clearLoginAttempts($_SERVER['REMOTE_ADDR']);
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

/**
 * Directory where rate limit data is stored.
 */
define('RATE_LIMIT_DIR', CMS_ROOT . '/db/.rate_limits');

/**
 * Maximum number of failed attempts before lockout.
 */
define('RATE_LIMIT_MAX_ATTEMPTS', 5);

/**
 * Base lockout duration in seconds (doubles with each subsequent lockout).
 */
define('RATE_LIMIT_BASE_LOCKOUT', 60);

/**
 * Checks if the given IP address is currently rate-limited.
 *
 * @param string $ipAddress  The client's IP address.
 * @return bool              TRUE if allowed to proceed, FALSE if rate-limited.
 */
function checkLoginRateLimit(string $ipAddress): bool
{
    $limitFile = getRateLimitFile($ipAddress);
    
    if (!file_exists($limitFile)) {
        return true;
    }
    
    $data = json_decode(file_get_contents($limitFile), true);
    if ($data === null) {
        return true;
    }
    
    $attempts = $data['attempts'] ?? 0;
    $lockoutUntil = $data['lockout_until'] ?? 0;
    
    // If currently in lockout period
    if ($lockoutUntil > time()) {
        return false;
    }
    
    // Reset if lockout period has expired
    if ($lockoutUntil > 0 && $lockoutUntil <= time()) {
        @unlink($limitFile);
        return true;
    }
    
    // Check if max attempts reached
    if ($attempts >= RATE_LIMIT_MAX_ATTEMPTS) {
        return false;
    }
    
    return true;
}

/**
 * Records a failed login attempt for the given IP address.
 *
 * @param string $ipAddress  The client's IP address.
 * @return void
 */
function recordFailedLogin(string $ipAddress): void
{
    $limitFile = getRateLimitFile($ipAddress);
    $now = time();
    
    // Create directory if it doesn't exist
    if (!is_dir(RATE_LIMIT_DIR)) {
        mkdir(RATE_LIMIT_DIR, 0750, true);
    }
    
    // Load existing data or initialize
    $data = ['attempts' => 0, 'lockout_until' => 0, 'last_attempt' => 0];
    if (file_exists($limitFile)) {
        $existing = json_decode(file_get_contents($limitFile), true);
        if (is_array($existing)) {
            $data = $existing;
        }
    }
    
    // Increment attempts
    $data['attempts'] = ($data['attempts'] ?? 0) + 1;
    $data['last_attempt'] = $now;
    
    // If max attempts reached, set lockout
    if ($data['attempts'] >= RATE_LIMIT_MAX_ATTEMPTS) {
        // Calculate lockout duration (exponential backoff based on total attempts)
        $totalLockouts = intdiv($data['attempts'], RATE_LIMIT_MAX_ATTEMPTS);
        $lockoutDuration = RATE_LIMIT_BASE_LOCKOUT * pow(2, $totalLockouts - 1);
        $data['lockout_until'] = $now + $lockoutDuration;
    }
    
    // Write data atomically
    $tempFile = $limitFile . '.tmp';
    file_put_contents($tempFile, json_encode($data));
    rename($tempFile, $limitFile);
}

/**
 * Clears all login attempt records for the given IP address.
 * Call this after a successful login.
 *
 * @param string $ipAddress  The client's IP address.
 * @return void
 */
function clearLoginAttempts(string $ipAddress): void
{
    $limitFile = getRateLimitFile($ipAddress);
    if (file_exists($limitFile)) {
        @unlink($limitFile);
    }
}

/**
 * Returns the remaining lockout time in seconds, or 0 if not locked out.
 *
 * @param string $ipAddress  The client's IP address.
 * @return int               Seconds remaining in lockout, or 0.
 */
function getRemainingLockoutTime(string $ipAddress): int
{
    $limitFile = getRateLimitFile($ipAddress);
    
    if (!file_exists($limitFile)) {
        return 0;
    }
    
    $data = json_decode(file_get_contents($limitFile), true);
    if ($data === null) {
        return 0;
    }
    
    $lockoutUntil = $data['lockout_until'] ?? 0;
    $remaining = $lockoutUntil - time();
    
    return max(0, $remaining);
}

/**
 * Gets the path to the rate limit file for an IP address.
 *
 * @param string $ipAddress  The client's IP address.
 * @return string            File path for storing rate limit data.
 */
function getRateLimitFile(string $ipAddress): string
{
    // Sanitize IP address for use as filename
    $safeIp = preg_replace('/[^a-zA-Z0-9._-]/', '_', $ipAddress);
    return RATE_LIMIT_DIR . '/' . $safeIp . '.json';
}
