<?php
/**
 * calendar/event.php
 *
 * Displays the full details of a single event.
 *
 * Route: event.php?id=<int>
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = $id > 0 ? getEvent($id) : null;

// ---------------------------------------------------------------------------
// JSON mode for the event modal (called from main.js)
// ---------------------------------------------------------------------------
if (!empty($_GET['json']) && $_GET['json'] === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    if ($event === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode([
        'id'          => (int) $event['id'],
        'title'       => $event['title'],
        'description' => $event['description'],
        'datetime'    => formatDatetime($event['start_datetime'])
                         . ' – ' . formatDatetime($event['end_datetime']),
        'location'    => $event['location'],
        'is_public'   => (bool) $event['is_public'],
    ]);
    exit;
}

if ($event === null) {
    http_response_code(404);
    $pageTitle = 'Event Not Found';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="alert alert--error">Event not found.</div>';
    echo '<p><a href="' . SITE_URL . '" class="btn btn--outline btn--sm">&#8592; Back to Calendar</a></p>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

$pageTitle = $event['title'];
$metaDesc  = !empty($event['description'])
    ? mb_substr(strip_tags($event['description']), 0, 160)
    : 'Event: ' . $event['title'];

require_once __DIR__ . '/templates/header.php';
?>

<div class="event-detail">

    <div class="event-detail__header">
        <a href="<?= SITE_URL ?>?year=<?= (new DateTime($event['start_datetime']))->format('Y') ?>&amp;month=<?= (new DateTime($event['start_datetime']))->format('n') ?>"
           class="btn btn--outline btn--sm back-link">
            &#8592; Back to Calendar
        </a>

        <div class="event-detail__actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/edit_event.php?id=<?= (int) $event['id'] ?>"
                   class="btn btn--outline btn--sm">Edit</a>
                <form method="post" action="<?= SITE_URL ?>/admin/events.php"
                      style="display:inline;"
                      onsubmit="return confirm('Delete this event? This cannot be undone.');">
                    <input type="hidden" name="action"     value="delete">
                    <input type="hidden" name="id"         value="<?= (int) $event['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <article class="event-card">
        <h1 class="event-card__title"><?= e($event['title']) ?></h1>

        <div class="event-card__meta">
            <div class="event-card__meta-item">
                <span class="meta-icon">&#128336;</span>
                <span>
                    <strong>Start:</strong>
                    <?= e(formatDatetime($event['start_datetime'])) ?>
                </span>
            </div>
            <div class="event-card__meta-item">
                <span class="meta-icon">&#9200;</span>
                <span>
                    <strong>End:</strong>
                    <?= e(formatDatetime($event['end_datetime'])) ?>
                </span>
            </div>
            <?php if (!empty($event['location'])): ?>
                <div class="event-card__meta-item">
                    <span class="meta-icon">&#128205;</span>
                    <span><?= e($event['location']) ?></span>
                </div>
            <?php endif; ?>
            <div class="event-card__meta-item">
                <span class="meta-icon">&#128065;</span>
                <span><?= $event['is_public'] ? 'Public' : 'Private' ?> event</span>
            </div>
        </div>

        <?php if (!empty($event['description'])): ?>
            <div class="event-card__desc">
                <?= nl2br(e($event['description'])) ?>
            </div>
        <?php endif; ?>

        <p class="event-card__created text-muted">
            Added <?= e(formatDate($event['created_at'], 'j M Y')) ?>
        </p>
    </article>

</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
