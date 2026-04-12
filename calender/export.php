<?php
/**
 * calendar/export.php
 *
 * Exports calendar events as a downloadable .ics file.
 *
 * Optional query-string filters:
 *   ?from=YYYY-MM-DD  – Include events on or after this date.
 *   ?to=YYYY-MM-DD    – Include events on or before this date.
 *   ?public=1         – Export public events only (default: all for logged-in users).
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/core/ics_generator.php';

// ---------------------------------------------------------------------------
// Parse optional filters
// ---------------------------------------------------------------------------

$fromRaw = trim($_GET['from'] ?? '');
$toRaw   = trim($_GET['to']   ?? '');
$pubOnly = !empty($_GET['public']);

$from = '';
$to   = '';

if ($fromRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromRaw)) {
    $from = $fromRaw . ' 00:00:00';
}
if ($toRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toRaw)) {
    $to = $toRaw . ' 23:59:59';
}

// ---------------------------------------------------------------------------
// If no download requested yet, show filter form
// ---------------------------------------------------------------------------

$download = !empty($_GET['download']);

if (!$download) {
    $pageTitle = 'Export Events';
    $metaDesc  = 'Export calendar events as an .ics file.';
    require_once __DIR__ . '/templates/header.php';
    ?>

    <div class="form-page">
        <div class="form-card">
            <div class="form-card__header">
                <a href="<?= SITE_URL ?>" class="back-link btn btn--outline btn--sm">&#8592; Calendar</a>
                <h1>&#8679; Export .ics</h1>
            </div>

            <p class="text-muted" style="margin-bottom:1.5rem;">
                Download your calendar events in iCalendar format (.ics) compatible with
                Google Calendar, Apple Calendar, and Outlook.
            </p>

            <form method="get" action="<?= SITE_URL ?>/export.php">
                <input type="hidden" name="download" value="1">

                <div class="form-row">
                    <div class="form-group">
                        <label for="from" class="form-label">From date <span class="optional">(optional)</span></label>
                        <input type="date" id="from" name="from"
                               class="form-input"
                               value="<?= e($fromRaw) ?>">
                    </div>
                    <div class="form-group">
                        <label for="to" class="form-label">To date <span class="optional">(optional)</span></label>
                        <input type="date" id="to" name="to"
                               class="form-input"
                               value="<?= e($toRaw) ?>">
                    </div>
                </div>

                <div class="form-group form-check">
                    <label class="check-label">
                        <input type="checkbox" name="public" value="1"
                               <?= $pubOnly ? 'checked' : '' ?>>
                        Public events only
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">&#8681; Download .ics</button>
                    <a href="<?= SITE_URL ?>" class="btn btn--outline">Cancel</a>
                </div>
            </form>

            <hr style="margin:2rem 0; border-color:var(--clr-border);">

            <h2 style="font-size:1rem; margin-bottom:.75rem;">Calendar Sync URL</h2>
            <p class="text-muted" style="font-size:.9rem; margin-bottom:.75rem;">
                Subscribe directly from your calendar app using this URL:
            </p>
            <code class="sync-url"><?= e(SITE_URL) ?>/sync.php</code>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Build query with optional filters
// ---------------------------------------------------------------------------

$db     = getDB();
$where  = [];
$params = [];

if ($from !== '') {
    $where[]          = 'start_datetime >= :from';
    $params[':from']  = $from;
}
if ($to !== '') {
    $where[]          = 'start_datetime <= :to';
    $params[':to']    = $to;
}
if ($pubOnly) {
    $where[] = 'is_public = 1';
}

$sql    = "SELECT * FROM cal_events";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY start_datetime ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// ---------------------------------------------------------------------------
// Generate and serve the .ics file
// ---------------------------------------------------------------------------

$gen     = new IcsGenerator();
$icsData = $gen->generate($events, SITE_NAME, SITE_TAGLINE);

$filename = 'calendar-export-' . date('Y-m-d') . '.ics';

header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Length: ' . strlen($icsData));

echo $icsData;
exit;
