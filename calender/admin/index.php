<?php
/**
 * admin/index.php
 *
 * Calendar admin dashboard. Shows at-a-glance statistics and
 * the most recently updated events.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$db = getDB();

// ---------------------------------------------------------------------------
// Statistics
// ---------------------------------------------------------------------------

$totalEvents  = (int) $db->query("SELECT COUNT(*) FROM cal_events")->fetchColumn();
$publicEvents = (int) $db->query("SELECT COUNT(*) FROM cal_events WHERE is_public = 1")->fetchColumn();
$totalTokens  = (int) $db->query("SELECT COUNT(*) FROM cal_tokens WHERE is_active = 1")->fetchColumn();

$upcoming = getUpcomingEvents(5);
$user     = currentUser();

// Most recently created events
$recentEvents = $db->query(
    "SELECT * FROM cal_events ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------

$currentAdminPage = 'dashboard';

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Dashboard – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>&#128197; Calendar Dashboard</h1>
            <span>Welcome back, <strong><?= e($user['username'] ?? 'Admin') ?></strong></span>
        </div>

        <?php renderFlash(); ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-card__number"><?= $totalEvents ?></span>
                <div class="stat-card__label">Total Events</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= $publicEvents ?></span>
                <div class="stat-card__label">Public Events</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= $totalEvents - $publicEvents ?></span>
                <div class="stat-card__label">Private Events</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= $totalTokens ?></span>
                <div class="stat-card__label">Active Sync Tokens</div>
            </div>
        </div>

        <!-- Quick actions -->
        <div style="display:flex;gap:.75rem;margin:1.5rem 0;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/create_event.php" class="btn btn--primary">+ New Event</a>
            <a href="<?= SITE_URL ?>/admin/events.php" class="btn btn--outline">Manage Events</a>
            <a href="<?= SITE_URL ?>/import.php"       class="btn btn--outline">Import .ics</a>
            <a href="<?= SITE_URL ?>/export.php"       class="btn btn--outline">Export .ics</a>
            <a href="<?= SITE_URL ?>/admin/tokens.php" class="btn btn--outline">Sync Tokens</a>
        </div>

        <!-- Recent Events -->
        <div class="admin-header" style="margin-top:.5rem;">
            <h2 style="font-size:1.1rem;">Recently Added Events</h2>
            <a href="<?= SITE_URL ?>/admin/events.php" class="btn btn--outline btn--sm">View All</a>
        </div>

        <?php if (empty($recentEvents)): ?>
            <p class="text-muted">No events yet. <a href="<?= SITE_URL ?>/create_event.php">Create the first one</a>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Visibility</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEvents as $ev): ?>
                            <tr>
                                <td>
                                    <a href="<?= SITE_URL ?>/event.php?id=<?= (int) $ev['id'] ?>">
                                        <?= e($ev['title']) ?>
                                    </a>
                                </td>
                                <td><?= e(formatDatetime($ev['start_datetime'])) ?></td>
                                <td><?= e(formatDatetime($ev['end_datetime'])) ?></td>
                                <td>
                                    <span class="badge <?= $ev['is_public'] ? 'badge--green' : 'badge--grey' ?>">
                                        <?= $ev['is_public'] ? 'Public' : 'Private' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= SITE_URL ?>/edit_event.php?id=<?= (int) $ev['id'] ?>"
                                       class="btn btn--outline btn--sm">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
