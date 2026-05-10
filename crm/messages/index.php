<?php
/**
 * crm/messages/index.php
 *
 * Inbox and sent messages for the current user.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db  = getCRMDB();
$uid = (int) ($_SESSION['user_id'] ?? 0);
$tab = ($_GET['tab'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';
$flash = crmGetFlash();

if ($tab === 'inbox') {
    $messages = $db->prepare(
        "SELECT m.*, u.username AS sender_name
         FROM crm_messages m
         LEFT JOIN users u ON u.id = m.sender_id
         WHERE m.recipient_id = :uid
         ORDER BY m.created_at DESC"
    );
    $messages->execute([':uid' => $uid]);
} else {
    $messages = $db->prepare(
        "SELECT m.*, u.username AS recipient_name
         FROM crm_messages m
         LEFT JOIN users u ON u.id = m.recipient_id
         WHERE m.sender_id = :uid
         ORDER BY m.created_at DESC"
    );
    $messages->execute([':uid' => $uid]);
}
$messages = $messages->fetchAll();

$pageTitle = 'Messages';
$activeNav = 'messages';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div><h1>&#128140; Messages</h1></div>
    <a href="<?= CRM_URL ?>/messages/compose.php" class="btn btn--primary btn--sm">&#9998; Compose</a>
</div>

<div class="crm-stage-bar">
    <a href="?tab=inbox" class="crm-stage-bar__item <?= $tab === 'inbox' ? 'crm-stage-bar__item--active' : '' ?>">Inbox</a>
    <a href="?tab=sent"  class="crm-stage-bar__item <?= $tab === 'sent'  ? 'crm-stage-bar__item--active' : '' ?>">Sent</a>
</div>

<?php if ($messages): ?>
    <div class="crm-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= $tab === 'inbox' ? 'From' : 'To' ?></th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr <?= ($tab === 'inbox' && !$m['is_read']) ? 'class="crm-row--unread"' : '' ?>>
                        <td>
                            <?php if ($tab === 'inbox'): ?>
                                <?= htmlspecialchars($m['sender_name'] ?? 'Unknown', ENT_QUOTES) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($m['recipient_name'] ?? 'Unknown', ENT_QUOTES) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= CRM_URL ?>/messages/view.php?id=<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['subject'], ENT_QUOTES) ?>
                            </a>
                            <?php if ($tab === 'inbox' && !$m['is_read']): ?>
                                <span class="badge badge--info" style="margin-left:.4rem">New</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= crmTimeAgo($m['created_at']) ?></td>
                        <td>
                            <?php if ($tab === 'inbox' && crmCanDelete()): ?>
                                <a href="<?= CRM_URL ?>/messages/delete.php?id=<?= $m['id'] ?>&csrf=<?= crmCsrfToken() ?>"
                                   class="btn btn--danger btn--sm"
                                   onclick="return confirm('Delete this message?')">Del</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <h2><?= $tab === 'inbox' ? 'Your inbox is empty' : 'No sent messages' ?></h2>
        <p><a href="<?= CRM_URL ?>/messages/compose.php" class="btn btn--primary">Compose a message</a></p>
    </div>
<?php endif; ?>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
