<?php
/**
 * admin/tokens.php
 *
 * Manages calendar sync tokens.
 * Allows authenticated users to create and revoke token-based feed URLs.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Handle POST actions (create / revoke token)
// ---------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        flashMessage('Invalid request.', 'error');
        redirect(SITE_URL . '/admin/tokens.php');
    }

    $action = $_POST['action'] ?? '';
    $userId = isLoggedIn()
        ? (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null)
        : null;

    if ($action === 'create') {
        $label = trim($_POST['label'] ?? 'My Calendar');
        $label = mb_substr($label, 0, 100);
        if ($label === '') $label = 'My Calendar';
        $token = createSyncToken($userId, $label);
        flashMessage('New sync token created.');
    } elseif ($action === 'revoke') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            revokeSyncToken($id);
            flashMessage('Token revoked.');
        }
    }

    redirect(SITE_URL . '/admin/tokens.php');
}

// ---------------------------------------------------------------------------
// Load tokens
// ---------------------------------------------------------------------------

$tokens = getSyncTokens();

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------

$currentAdminPage = 'tokens';

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Sync Tokens – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>&#128273; Sync Tokens</h1>
        </div>

        <?php renderFlash(); ?>

        <p class="text-muted" style="margin-bottom:1.5rem; max-width:640px;">
            Sync tokens let external calendar apps (Google Calendar, Apple Calendar, Outlook)
            subscribe to a private feed of <em>all</em> your events.
            The public feed <code><?= e(SITE_URL) ?>/sync.php</code> requires no token.
        </p>

        <!-- Create token form -->
        <div class="form-card" style="max-width:480px; margin-bottom:2rem;">
            <h2 style="font-size:1rem; margin-bottom:1rem;">Create New Token</h2>
            <form method="post" action="<?= SITE_URL ?>/admin/tokens.php">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action"     value="create">
                <div class="form-group">
                    <label for="label" class="form-label">Label</label>
                    <input type="text" id="label" name="label"
                           class="form-input"
                           value="My Calendar"
                           maxlength="100">
                </div>
                <button type="submit" class="btn btn--primary btn--sm">Generate Token</button>
            </form>
        </div>

        <!-- Token list -->
        <?php if (empty($tokens)): ?>
            <p class="text-muted">No active tokens yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Feed URL</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $tok): ?>
                            <?php $feedUrl = SITE_URL . '/sync.php?token=' . rawurlencode($tok['token']); ?>
                            <tr>
                                <td><?= e($tok['label']) ?></td>
                                <td>
                                    <code class="token-url" onclick="copyToClipboard(this)"
                                          title="Click to copy">
                                        <?= e($feedUrl) ?>
                                    </code>
                                </td>
                                <td><?= e(formatDate($tok['created_at'], 'j M Y')) ?></td>
                                <td>
                                    <form method="post"
                                          action="<?= SITE_URL ?>/admin/tokens.php"
                                          style="display:inline;"
                                          onsubmit="return confirm('Revoke this token? Subscribed apps will lose access.');">
                                        <input type="hidden" name="action"     value="revoke">
                                        <input type="hidden" name="id"         value="<?= (int) $tok['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Revoke</button>
                                    </form>
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
