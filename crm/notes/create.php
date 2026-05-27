<?php
/**
 * crm/notes/create.php
 *
 * Adds a note to a customer, lead, or company.
 * Expects ?type=<entity>&id=<entity_id> on GET.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db     = getCRMDB();
$errors = [];

$relType = trim($_REQUEST['type'] ?? '');
$relId   = (int) ($_REQUEST['id']   ?? 0);

$validTypes = ['customer', 'lead', 'company'];
if (!in_array($relType, $validTypes, true) || $relId <= 0) {
    crmFlash('Invalid request.', 'error');
    crmRedirect(CRM_URL . '/');
}

$returnUrl = CRM_URL . '/' . $relType . 's/view.php?id=' . $relId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $content   = trim($_POST['content']    ?? '');
        $isPrivate = isset($_POST['is_private']) ? 1 : 0;

        if ($content === '') { $errors[] = 'Note content cannot be empty.'; }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO crm_notes (related_type, related_id, content, is_private, created_by)
                 VALUES (:rtype, :rid, :content, :private, :uid)"
            )->execute([
                ':rtype'   => $relType,
                ':rid'     => $relId,
                ':content' => $content,
                ':private' => $isPrivate,
                ':uid'     => (int) ($_SESSION['user_id'] ?? 0),
            ]);
            crmLogActivity('added note', $relType, $relId, mb_strimwidth($content, 0, 50, '…'));
            crmFlash('Note added.', 'success');
            crmRedirect($returnUrl);
        }
    }
}

$pageTitle = 'Add Note';
$activeNav = 'customers'; // generic fallback
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#128203; Add Note</h1>
        <p><a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES) ?>">&#8592; Back</a></p>
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
        <input type="hidden" name="type" value="<?= htmlspecialchars($relType, ENT_QUOTES) ?>">
        <input type="hidden" name="id"   value="<?= $relId ?>">

        <div class="form-group">
            <label for="content">Note</label>
            <textarea id="content" name="content" class="form-control" rows="6" required><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-group">
            <label style="display:flex;gap:.5rem;align-items:center">
                <input type="checkbox" name="is_private" <?= isset($_POST['is_private']) ? 'checked' : '' ?>>
                Private note (only visible to admins and editors)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Note</button>
            <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES) ?>" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
