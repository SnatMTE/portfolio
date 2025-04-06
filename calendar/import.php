<?php
/**
 * calendar/import.php
 *
 * Allows an authenticated user to upload an .ics file and import its events.
 *
 * Security measures:
 *   - CSRF token validation
 *   - File MIME and extension validation
 *   - Content sanitised through IcsParser before DB insertion
 *   - PDO prepared statements in createEvent()
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/core/ics_parser.php';

// Require login to import
if (!isLoggedIn()) {
    $loginUrl = defined('CMS_URL') ? CMS_URL . '/login.php' : SITE_URL . '/login.php';
    redirect($loginUrl);
}

$errors   = [];
$imported = 0;
$skipped  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid request. Please try again.';
    } elseif (empty($_FILES['ics_file']['tmp_name']) || $_FILES['ics_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'No file uploaded or upload error occurred.';
    } else {
        $file     = $_FILES['ics_file'];
        $origName = $file['name'] ?? '';
        $tmpPath  = $file['tmp_name'];
        $size     = (int) ($file['size'] ?? 0);

        // Validate extension
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext !== 'ics') {
            $errors[] = 'Only .ics files are accepted.';
        }

        // Validate size (max 2 MB)
        if ($size > 2 * 1024 * 1024) {
            $errors[] = 'File size must not exceed 2 MB.';
        }

        if (empty($errors)) {
            // Read and check file content - basic magic bytes check
            $content = file_get_contents($tmpPath);
            if ($content === false) {
                $errors[] = 'Could not read the uploaded file.';
            } elseif (strpos($content, 'BEGIN:VCALENDAR') === false) {
                $errors[] = 'The file does not appear to be a valid iCalendar (.ics) file.';
            } else {
                $parser = new IcsParser();
                $events = $parser->parse($content);

                $userId = isLoggedIn()
                    ? (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null)
                    : null;

                foreach ($events as $ev) {
                    // Basic sanity check: skip events with invalid dates
                    if (empty($ev['start_datetime']) || empty($ev['end_datetime'])) {
                        $skipped++;
                        continue;
                    }
                    try {
                        createEvent([
                            'user_id'        => $userId,
                            'title'          => mb_substr($ev['title'],       0, 200),
                            'description'    => mb_substr($ev['description'], 0, 5000),
                            'start_datetime' => $ev['start_datetime'],
                            'end_datetime'   => $ev['end_datetime'],
                            'location'       => mb_substr($ev['location'],    0, 300),
                            'is_public'      => 1,
                        ]);
                        $imported++;
                    } catch (PDOException $e) {
                        $skipped++;
                    }
                }

                if ($imported > 0) {
                    flashMessage("Import complete: {$imported} event(s) added." .
                        ($skipped > 0 ? " {$skipped} skipped." : ''));
                    redirect(SITE_URL);
                } else {
                    $errors[] = 'No valid events found in the file. ' .
                        ($skipped > 0 ? "{$skipped} event(s) were skipped due to missing data." : '');
                }
            }
        }
    }
}

$pageTitle = 'Import Events';
$metaDesc  = 'Import calendar events from an .ics file.';
require_once __DIR__ . '/templates/header.php';
?>

<div class="form-page">
    <div class="form-card">
        <div class="form-card__header">
            <a href="<?= SITE_URL ?>" class="back-link btn btn--outline btn--sm">&#8592; Calendar</a>
            <h1>&#8681; Import .ics</h1>
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

        <p class="text-muted" style="margin-bottom:1.5rem;">
            Upload an iCalendar (.ics) file exported from Google Calendar, Apple Calendar,
            Outlook, or any compatible application. All events in the file will be imported.
        </p>

        <form method="post"
              action="<?= SITE_URL ?>/import.php"
              enctype="multipart/form-data"
              novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="ics_file" class="form-label">
                    .ics File <span class="required">*</span>
                </label>
                <input type="file"
                       id="ics_file"
                       name="ics_file"
                       class="form-input"
                       accept=".ics,text/calendar"
                       required>
                <p class="form-hint">Maximum file size: 2 MB.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">&#8681; Import Events</button>
                <a href="<?= SITE_URL ?>" class="btn btn--outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
