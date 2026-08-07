<?php
/**
 * crm/messages/view.php
 *
 * View a single message. Marks it as read if the current user is the recipient.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db  = getCRMDB();
$uid = (int) ($_SESSION['user_id'] ?? 0);
$id  = (int) ($_GET['id'] ?? 0);

if ($id <= 0) { crmRedirect(CRM_URL . '/messages/'); }

$stmt = $db->prepare(
    "SELECT m.*, s.username AS sender_name, r.username AS recipient_name
     FROM crm_messages m
     LEFT JOIN users s ON s.id = m.sender_id
     LEFT JOIN users r ON r.id = m.recipient_id
     WHERE m.id = :id"
);
$stmt->execute([':id' => $id]);
$msg = $stmt->fetch();

if (!$msg) { http_response_code(404); exit('Message not found.'); }

// Only sender or recipient may view the message.
if ((int) $msg['sender_id'] !== $uid && (int) $msg['recipient_id'] !== $uid) {
    http_response_code(403);
    exit('Access denied.');
}

// Mark as read when the recipient opens it.
if ((int) $msg['recipient_id'] === $uid && !$msg['is_read']) {
    $db->prepare("UPDATE crm_messages SET is_read = 1 WHERE id = :id")->execute([':id' => $id]);
}

$pageTitle = htmlspecialchars($msg['subject'], ENT_QUOTES);
$activeNav = 'messages';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#128140; <?= htmlspecialchars($msg['subject'], ENT_QUOTES) ?></h1>
    </div>
    <div class="admin-header__actions">
        <?php if ((int) $msg['recipient_id'] === $uid): ?>
            <a href="<?= CRM_URL ?>/messages/compose.php?reply_to=<?= $id ?>" class="btn btn--primary btn--sm">&#8629; Reply</a>
        <?php endif; ?>
        <a href="<?= CRM_URL ?>/messages/" class="btn btn--outline btn--sm">&#8592; Back</a>
    </div>
</div>

<div class="admin-form-card">
    <dl class="crm-dl" style="margin-bottom:1.5rem">
        <dt>From</dt>
        <dd><?= htmlspecialchars($msg['sender_name'] ?? '—', ENT_QUOTES) ?></dd>
        <dt>To</dt>
        <dd><?= htmlspecialchars($msg['recipient_name'] ?? '—', ENT_QUOTES) ?></dd>
        <dt>Date</dt>
        <dd><?= crmFormatDate($msg['created_at'], 'j M Y, H:i') ?></dd>
    </dl>
    <div style="white-space:pre-wrap;line-height:1.7"><?= nl2br(htmlspecialchars($msg['body'], ENT_QUOTES)) ?></div>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
