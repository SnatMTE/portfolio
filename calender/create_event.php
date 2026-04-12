<?php
/**
 * calendar/create_event.php
 *
 * Form to create a new calendar event.
 * Accessible to anyone; no login required for public event submission.
 *
 * Query string:
 *   ?date=YYYY-MM-DD  – Pre-fills the start date (clicked from grid).
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$errors = [];
$values = [
    'title'          => '',
    'description'    => '',
    'start_datetime' => '',
    'end_datetime'   => '',
    'location'       => '',
    'is_public'      => 1,
];

// Pre-fill date from ?date= query string
if (!empty($_GET['date'])) {
    $preDate = preg_replace('/[^0-9\-]/', '', $_GET['date']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $preDate)) {
        $values['start_datetime'] = $preDate . 'T09:00';
        $values['end_datetime']   = $preDate . 'T10:00';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Collect and sanitise input
        $values['title']          = trim($_POST['title']       ?? '');
        $values['description']    = trim($_POST['description'] ?? '');
        $values['start_datetime'] = trim($_POST['start_datetime'] ?? '');
        $values['end_datetime']   = trim($_POST['end_datetime']   ?? '');
        $values['location']       = trim($_POST['location']    ?? '');
        $values['is_public']      = isset($_POST['is_public']) ? 1 : 0;

        // Validate
        if ($values['title'] === '') {
            $errors[] = 'Event title is required.';
        } elseif (mb_strlen($values['title']) > 200) {
            $errors[] = 'Title must be 200 characters or fewer.';
        }

        if ($values['start_datetime'] === '') {
            $errors[] = 'Start date/time is required.';
        }

        if ($values['end_datetime'] === '') {
            $errors[] = 'End date/time is required.';
        }

        if ($values['start_datetime'] !== '' && $values['end_datetime'] !== '') {
            $start = strtotime($values['start_datetime']);
            $end   = strtotime($values['end_datetime']);
            if ($start === false || $end === false) {
                $errors[] = 'Invalid date format.';
            } elseif ($end < $start) {
                $errors[] = 'End date/time must be after start date/time.';
            }
        }

        if (empty($errors)) {
            // Normalise datetime to "YYYY-MM-DD HH:MM:SS"
            $startDb = date('Y-m-d H:i:s', strtotime($values['start_datetime']));
            $endDb   = date('Y-m-d H:i:s', strtotime($values['end_datetime']));

            $userId = isLoggedIn() ? (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null) : null;

            $newId = createEvent([
                'user_id'        => $userId,
                'title'          => $values['title'],
                'description'    => $values['description'],
                'start_datetime' => $startDb,
                'end_datetime'   => $endDb,
                'location'       => $values['location'],
                'is_public'      => $values['is_public'],
            ]);

            flashMessage('Event "' . $values['title'] . '" created successfully.');
            redirect(SITE_URL . '/event.php?id=' . $newId);
        }
    }
}

$pageTitle = 'New Event';
$metaDesc  = 'Add a new event to the calendar.';
require_once __DIR__ . '/templates/header.php';
?>

<div class="form-page">
    <div class="form-card">
        <div class="form-card__header">
            <a href="<?= SITE_URL ?>" class="back-link btn btn--outline btn--sm">&#8592; Calendar</a>
            <h1>New Event</h1>
        </div>

        <?php renderFlash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error">
                <ul style="margin:0; padding-left:1.25rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/create_event.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="title" class="form-label">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       class="form-input"
                       value="<?= e($values['title']) ?>"
                       maxlength="200"
                       required
                       autofocus>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_datetime" class="form-label">Start <span class="required">*</span></label>
                    <input type="datetime-local" id="start_datetime" name="start_datetime"
                           class="form-input"
                           value="<?= e($values['start_datetime']) ?>"
                           required>
                </div>
                <div class="form-group">
                    <label for="end_datetime" class="form-label">End <span class="required">*</span></label>
                    <input type="datetime-local" id="end_datetime" name="end_datetime"
                           class="form-input"
                           value="<?= e($values['end_datetime']) ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="location" class="form-label">Location <span class="optional">(optional)</span></label>
                <input type="text" id="location" name="location"
                       class="form-input"
                       value="<?= e($values['location']) ?>"
                       maxlength="300"
                       placeholder="e.g. London, Online, Room 4B">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description <span class="optional">(optional)</span></label>
                <textarea id="description" name="description"
                          class="form-input form-textarea"
                          rows="5"
                          placeholder="Event details, agenda, notes…"><?= e($values['description']) ?></textarea>
            </div>

            <div class="form-group form-check">
                <label class="check-label">
                    <input type="checkbox" name="is_public" value="1"
                           <?= $values['is_public'] ? 'checked' : '' ?>>
                    Public event (visible in the sync feed)
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Create Event</button>
                <a href="<?= SITE_URL ?>" class="btn btn--outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
