<?php
/**
 * crm/messages/compose.php
 *
 * Compose a new internal CRM message. Supports replying via ?reply_to=<id>.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db     = getCRMDB();
$uid    = (int) ($_SESSION['user_id'] ?? 0);
$errors = [];

$formData = ['recipient_id' => 0, 'subject' => '', 'body' => ''];

// Pre-fill reply data if replying to an existing message.
$replyToId = (int) ($_GET['reply_to'] ?? 0);
if ($replyToId > 0) {
    $orig = $db->prepare("SELECT * FROM crm_messages WHERE id = :id AND recipient_id = :uid");
    $orig->execute([':id' => $replyToId, ':uid' => $uid]);
    $orig = $orig->fetch();
    if ($orig) {
        $formData['recipient_id'] = $orig['sender_id'];
        $formData['subject']      = str_starts_with($orig['subject'], 'Re: ') ? $orig['subject'] : 'Re: ' . $orig['subject'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $formData = [
            'recipient_id' => (int) ($_POST['recipient_id'] ?? 0),
            'subject'      => trim($_POST['subject'] ?? ''),
            'body'         => trim($_POST['body']    ?? ''),
        ];

        if ($formData['recipient_id'] <= 0)  { $errors[] = 'Please select a recipient.'; }
        if ($formData['subject'] === '')      { $errors[] = 'Subject is required.'; }
        if ($formData['body'] === '')         { $errors[] = 'Message body is required.'; }
        if ($formData['recipient_id'] === $uid) { $errors[] = 'You cannot send a message to yourself.'; }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO crm_messages (sender_id, recipient_id, subject, body)
                 VALUES (:sender, :recipient, :subject, :body)"
            )->execute([
                ':sender'    => $uid,
                ':recipient' => $formData['recipient_id'],
                ':subject'   => $formData['subject'],
                ':body'      => $formData['body'],
            ]);
            crmFlash('Message sent.', 'success');
            crmRedirect(SITE_URL . '/crm/messages/?tab=sent');
        }
    }
}

// All users except the current one are valid recipients.
$users = array_filter(crmGetUsers(), fn($u) => (int) $u['id'] !== $uid);

$pageTitle = 'Compose Message';
$activeNav = 'messages';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#9998; Compose Message</h1>
        <p><a href="<?= SITE_URL ?>/crm/messages/">&#8592; Back to Messages</a></p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-form-card">
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= crmCsrfToken() ?>">

        <div class="form-group">
            <label for="recipient_id">To</label>
            <select id="recipient_id" name="recipient_id" class="form-control" required>
                <option value="">— Select recipient —</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (($formData['recipient_id'] ?? 0) == $u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" class="form-control" required
                   value="<?= htmlspecialchars($formData['subject'] ?? '', ENT_QUOTES) ?>" maxlength="255">
        </div>

        <div class="form-group">
            <label for="body">Message</label>
            <textarea id="body" name="body" class="form-control" rows="8" required><?= htmlspecialchars($formData['body'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Send Message</button>
            <a href="<?= SITE_URL ?>/crm/messages/" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
