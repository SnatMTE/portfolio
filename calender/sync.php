<?php
/**
 * calendar/sync.php
 *
 * Provides a live calendar feed in iCalendar (.ics) format.
 * External applications (Google Calendar, Apple Calendar, Outlook) can
 * subscribe to this URL to receive automatic updates.
 *
 * Access modes
 * ------------
 *   Public feed  : /sync.php
 *                  Returns all public events; no authentication required.
 *
 *   Private feed : /sync.php?token=<TOKEN>
 *                  Returns all events for that token's owner.
 *                  Token is generated in the admin panel.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/core/ics_generator.php';

// ---------------------------------------------------------------------------
// Determine access level
// ---------------------------------------------------------------------------

$tokenParam = trim($_GET['token'] ?? '');
$isPrivate  = false;

if ($tokenParam !== '') {
    // Validate token to prevent enumeration: only alphanumeric hex chars expected
    if (!preg_match('/^[0-9a-f]{48}$/', $tokenParam)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Invalid token.';
        exit;
    }

    if (!validateSyncToken($tokenParam)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Token not found or revoked.';
        exit;
    }

    $isPrivate = true;
}

// ---------------------------------------------------------------------------
// Fetch events
// ---------------------------------------------------------------------------

if ($isPrivate) {
    $events = getAllEvents();     // Authenticated token: return all events
} else {
    $events = getPublicEvents();  // Public: only is_public = 1 events
}

// ---------------------------------------------------------------------------
// Generate and output the .ics feed
// ---------------------------------------------------------------------------

$gen     = new IcsGenerator();
$icsData = $gen->generate($events, SITE_NAME, SITE_TAGLINE);

header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: inline; filename="calendar.ics"');

// Allow proxies/clients to cache for 15 minutes
header('Cache-Control: public, max-age=900');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

echo $icsData;
exit;
