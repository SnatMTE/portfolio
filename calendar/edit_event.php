<?php
/**
 * calendar/edit_event.php
 *
 * Form to edit an existing calendar event.
 * Requires a logged-in session.
 *
 * Route: edit_event.php?id=<int>
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Require login to edit
if (!isLoggedIn()) {
    $loginUrl = defined('CMS_URL') ? CMS_URL . '/login.php' : SITE_URL . '/login.php';
    redirect($loginUrl . '?redirect=' . urlencode(SITE_URL . '/edit_event.php?id=' . (int) ($_GET['id'] ?? 0)));
}

$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = $id > 0 ? getEvent($id) : null;

if ($event === null) {
    http_response_code(404);
    $pageTitle = 'Event Not Found';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="alert alert--error">Event not found.</div>';
    echo '<p><a href="' . SITE_URL . '" class="btn btn--outline btn--sm">&#8592; Calendar</a></p>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

$errors = [];
$values = [
    'title'          => $event['title'],
    'description'    => $event['description'],
    'start_datetime' => toInputDatetime($event['start_datetime']),
    'end_datetime'   => toInputDatetime($event['end_datetime']),
    'location'       => $event['location'],
    'is_public'      => (int) $event['is_public'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $values['title']          = trim($_POST['title']       ?? '');
        $values['description']    = trim($_POST['description'] ?? '');
        $values['start_datetime'] = trim($_POST['start_datetime'] ?? '');
        $values['end_datetime']   = trim($_POST['end_datetime']   ?? '');
        $values['location']       = trim($_POST['location']    ?? '');
        $values['is_public']      = isset($_POST['is_public']) ? 1 : 0;

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
            updateEvent($id, [
                'title'          => $values['title'],
                'description'    => $values['description'],
                'start_datetime' => date('Y-m-d H:i:s', strtotime($values['start_datetime'])),
                'end_datetime'   => date('Y-m-d H:i:s', strtotime($values['end_datetime'])),
                'location'       => $values['location'],
                'is_public'      => $values['is_public'],
            ]);

            flashMessage('Event updated successfully.');
            redirect(SITE_URL . '/event.php?id=' . $id);
        }
    }
}

$pageTitle = 'Edit: ' . $event['title'];
require_once __DIR__ . '/templates/header.php';
?>

<div class="form-page">
    <div class="form-card">
        <div class="form-card__header">
            <a href="<?= SITE_URL ?>/event.php?id=<?= $id ?>" class="back-link btn btn--outline btn--sm">
                &#8592; Back to Event
            </a>
            <h1>Edit Event</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error">
                <ul style="margin:0; padding-left:1.25rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/edit_event.php?id=<?= $id ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="title" class="form-label">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       class="form-input"
                       value="<?= e($values['title']) ?>"
                       maxlength="200"
                       required autofocus>
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
                       maxlength="300">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description <span class="optional">(optional)</span></label>
                <textarea id="description" name="description"
                          class="form-input form-textarea"
                          rows="5"><?= e($values['description']) ?></textarea>
            </div>

            <div class="form-group form-check">
                <label class="check-label">
                    <input type="checkbox" name="is_public" value="1"
                           <?= $values['is_public'] ? 'checked' : '' ?>>
                    Public event
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">Save Changes</button>
                <a href="<?= SITE_URL ?>/event.php?id=<?= $id ?>" class="btn btn--outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
