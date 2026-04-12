<?php
/**
 * calendar/index.php
 *
 * Primary calendar view — renders an interactive monthly grid.
 *
 * Query string parameters:
 *   ?year=2026&month=4   – Navigate to a specific month/year.
 *                          Defaults to the current month.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/core/calendar_helper.php';

// ---------------------------------------------------------------------------
// Determine which month to display
// ---------------------------------------------------------------------------

$year  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');

// Clamp to valid ranges
$year  = max(2000, min(2100, $year));
$month = max(1,    min(12,   $month));

// ---------------------------------------------------------------------------
// Load events for this month and build grid
// ---------------------------------------------------------------------------

$events   = getEventsByMonth($year, $month);
$grid     = buildMonthGrid($year, $month, $events);
$upcoming = getUpcomingEvents(5);

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------

$pageTitle = monthName($month) . ' ' . $year . ' – Calendar';
$metaDesc  = 'Monthly event calendar for ' . monthName($month) . ' ' . $year;

require_once __DIR__ . '/templates/header.php';
renderFlash();
?>

<div class="cal-layout">

    <!-- ── Left: monthly grid ────────────────────────────────── -->
    <section class="cal-main" aria-label="Monthly calendar">
        <?php require __DIR__ . '/templates/calendar_grid.php'; ?>
    </section>

    <!-- ── Right: sidebar ───────────────────────────────────── -->
    <aside class="cal-sidebar">

        <!-- Today button -->
        <a href="<?= SITE_URL ?>" class="btn btn--primary btn--full" style="margin-bottom:1rem;">
            &#128197; Today
        </a>

        <!-- Add event -->
        <a href="<?= SITE_URL ?>/create_event.php" class="btn btn--outline btn--full" style="margin-bottom:1.5rem;">
            + New Event
        </a>

        <!-- Upcoming events -->
        <div class="sidebar-section">
            <h3 class="sidebar-section__title">Upcoming Events</h3>
            <?php if (empty($upcoming)): ?>
                <p class="text-muted">No upcoming events.</p>
            <?php else: ?>
                <?php foreach ($upcoming as $event): ?>
                    <?php require __DIR__ . '/templates/event_item.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Quick export link -->
        <div class="sidebar-section" style="margin-top:1.5rem;">
            <h3 class="sidebar-section__title">Calendar Feed</h3>
            <p class="text-muted" style="margin-bottom:.75rem; font-size:.85rem;">
                Subscribe in Google Calendar, Apple Calendar, or Outlook:
            </p>
            <code class="sync-url"><?= e(SITE_URL) ?>/sync.php</code>
            <div style="margin-top:.75rem; display:flex; gap:.5rem; flex-wrap:wrap;">
                <a href="<?= SITE_URL ?>/sync.php" class="btn btn--outline btn--sm" download>
                    &#8681; Download .ics
                </a>
                <a href="<?= SITE_URL ?>/export.php" class="btn btn--outline btn--sm">
                    Export filtered
                </a>
            </div>
        </div>

    </aside>

</div>

<?php require __DIR__ . '/templates/event_modal.php'; ?>
<?php require __DIR__ . '/templates/footer.php'; ?>
