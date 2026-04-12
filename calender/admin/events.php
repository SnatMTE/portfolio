<?php
/**
 * admin/events.php
 *
 * Manages all calendar events: lists, deletes, links to edit.
 * Handles POST deletion with CSRF protection.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Handle POST actions (delete)
// ---------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        flashMessage('Invalid request.', 'error');
        redirect(SITE_URL . '/admin/events.php');
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $ev = getEvent($id);
        if ($ev !== null) {
            deleteEvent($id);
            flashMessage('Event "' . $ev['title'] . '" deleted.');
        }
    }

    redirect(SITE_URL . '/admin/events.php');
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

$page       = max(1, (int) ($_GET['page'] ?? 1));
$total      = countEvents();
$perPage    = EVENTS_PER_PAGE;
$totalPages = (int) ceil($total / $perPage);
$events     = getEvents($page, $perPage);

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------

$currentAdminPage = 'events';

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Events – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>All Events</h1>
            <a href="<?= SITE_URL ?>/create_event.php" class="btn btn--primary btn--sm">+ New Event</a>
        </div>

        <?php renderFlash(); ?>

        <?php if (empty($events)): ?>
            <p class="text-muted">No events found.
                <a href="<?= SITE_URL ?>/create_event.php">Create the first event</a>.</p>
        <?php else: ?>

            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start</th>
                            <th>Location</th>
                            <th>Visibility</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td>
                                    <a href="<?= SITE_URL ?>/event.php?id=<?= (int) $ev['id'] ?>">
                                        <?= e($ev['title']) ?>
                                    </a>
                                </td>
                                <td><?= e(formatDatetime($ev['start_datetime'])) ?></td>
                                <td><?= e($ev['location'] ?: '—') ?></td>
                                <td>
                                    <span class="badge <?= $ev['is_public'] ? 'badge--green' : 'badge--grey' ?>">
                                        <?= $ev['is_public'] ? 'Public' : 'Private' ?>
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="<?= SITE_URL ?>/edit_event.php?id=<?= (int) $ev['id'] ?>"
                                       class="btn btn--outline btn--sm">Edit</a>

                                    <form method="post"
                                          action="<?= SITE_URL ?>/admin/events.php"
                                          style="display:inline;"
                                          onsubmit="return confirm('Delete \'<?= addslashes(e($ev['title'])) ?>\' permanently?');">
                                        <input type="hidden" name="action"     value="delete">
                                        <input type="hidden" name="id"         value="<?= (int) $ev['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Events pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn btn--outline btn--sm">&#8249; Prev</a>
                    <?php endif; ?>

                    <span class="pagination__info">
                        Page <?= $page ?> of <?= $totalPages ?>
                        (<?= $total ?> events)
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn btn--outline btn--sm">Next &#8250;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
