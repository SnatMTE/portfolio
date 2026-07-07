<?php
/**
 * cms/core/security_headers.php
 *
 * Sets HTTP security headers for all CMS and module pages.
 * Should be included early in the request lifecycle, before any output.
 *
 * Headers set:
 *   - X-Frame-Options: DENY (prevent clickjacking)
 *   - X-Content-Type-Options: nosniff (prevent MIME sniffing)
 *   - X-XSS-Protection: 0 (rely on CSP instead)
 *   - Referrer-Policy: strict-origin-when-cross-origin
 *   - Permissions-Policy: restrict browser features
 *   - Content-Security-Policy: basic policy (customizable per page)
 *
 * Usage:
 *   require_once CMS_ROOT . '/core/security_headers.php';
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

// Only set headers if they haven't been sent yet
if (!headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Disable legacy XSS protection (CSP is preferred)
    header('X-XSS-Protection: 0');
    
    // Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Restrict browser features
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Basic Content Security Policy
    // Note: Individual pages can override this with more specific policies
    $cspDirectives = [
        "default-src 'self'",
        "script-src 'self' https://cdn.quilljs.com",
        "style-src 'self' https://fonts.googleapis.com 'unsafe-inline'",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: https://www.gravatar.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
    ];
    
    header('Content-Security-Policy: ' . implode('; ', $cspDirectives));
}
