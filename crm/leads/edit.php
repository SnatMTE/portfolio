<?php
/**
 * crm/leads/edit.php
 *
 * Edit an existing lead.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db     = getCRMDB();
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

if ($id <= 0) { crmRedirect(SITE_URL . '/crm/leads/'); }

$stmt = $db->prepare("SELECT * FROM crm_leads WHERE id = :id");
$stmt->execute([':id' => $id]);
$lead = $stmt->fetch();
if (!$lead) { http_response_code(404); exit('Lead not found.'); }

$formData = $lead;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $formData = [
            'title'         => trim($_POST['title']         ?? ''),
            'customer_id'   => (int) ($_POST['customer_id']  ?? 0),
            'company_id'    => (int) ($_POST['company_id']   ?? 0),
            'contact_name'  => trim($_POST['contact_name']  ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'value'         => (float) str_replace(',', '', $_POST['value'] ?? '0'),
            'currency'      => trim($_POST['currency']      ?? 'GBP'),
            'stage'         => trim($_POST['stage']         ?? 'new'),
            'priority'      => trim($_POST['priority']      ?? 'medium'),
            'source'        => trim($_POST['source']        ?? ''),
            'close_date'    => trim($_POST['close_date']    ?? ''),
            'assigned_to'   => (int) ($_POST['assigned_to'] ?? 0),
            'notes'         => trim($_POST['notes']         ?? ''),
        ];

        if ($formData['title'] === '') { $errors[] = 'Lead title is required.'; }
        $validStages   = ['new','contacted','qualified','proposal','negotiation','won','lost'];
        $validPriority = ['low','medium','high','urgent'];
        if (!in_array($formData['stage'], $validStages, true))      $formData['stage']    = 'new';
        if (!in_array($formData['priority'], $validPriority, true)) $formData['priority'] = 'medium';

        $closeDate = null;
        if ($formData['close_date'] !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $formData['close_date']);
            $closeDate = $dt ? $dt->format('Y-m-d') : null;
        }

        if (empty($errors)) {
            $db->prepare(
                "UPDATE crm_leads SET
                    title         = :title,
                    customer_id   = :customer_id,
                    company_id    = :company_id,
                    contact_name  = :contact_name,
                    contact_email = :contact_email,
                    contact_phone = :contact_phone,
                    value         = :value,
                    currency      = :currency,
                    stage         = :stage,
                    priority      = :priority,
                    source        = :source,
                    close_date    = :close_date,
                    assigned_to   = :assigned_to,
                    notes         = :notes,
                    updated_at    = datetime('now')
                 WHERE id = :id"
            )->execute([
                ':title'         => $formData['title'],
                ':customer_id'   => $formData['customer_id']   ?: null,
                ':company_id'    => $formData['company_id']    ?: null,
                ':contact_name'  => $formData['contact_name'],
                ':contact_email' => $formData['contact_email'],
                ':contact_phone' => $formData['contact_phone'],
                ':value'         => $formData['value'],
                ':currency'      => $formData['currency'],
                ':stage'         => $formData['stage'],
                ':priority'      => $formData['priority'],
                ':source'        => $formData['source'],
                ':close_date'    => $closeDate,
                ':assigned_to'   => $formData['assigned_to']   ?: null,
                ':notes'         => $formData['notes'],
                ':id'            => $id,
            ]);
            crmLogActivity('updated', 'lead', $id, $formData['title']);
            crmFlash('Lead updated.', 'success');
            crmRedirect(SITE_URL . '/crm/leads/view.php?id=' . $id);
        }
    }
}

$customers = $db->query("SELECT id, first_name || ' ' || last_name AS name FROM crm_customers ORDER BY last_name ASC")->fetchAll();
$companies = $db->query("SELECT id, name FROM crm_companies ORDER BY name ASC")->fetchAll();
$users     = crmGetUsers();

$pageTitle = 'Edit Lead';
$activeNav = 'leads';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#127919; Edit Lead</h1>
        <p><a href="<?= SITE_URL ?>/crm/leads/view.php?id=<?= $id ?>">&#8592; Back to Lead</a></p>
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
            <label for="title">Lead Title</label>
            <input type="text" id="title" name="title" class="form-control" required
                   value="<?= htmlspecialchars($formData['title'] ?? '', ENT_QUOTES) ?>" maxlength="255">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="customer_id">Linked Customer</label>
                <select id="customer_id" name="customer_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($customers as $cu): ?>
                        <option value="<?= $cu['id'] ?>" <?= (($formData['customer_id'] ?? 0) == $cu['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cu['name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="company_id">Linked Company</label>
                <select id="company_id" name="company_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($companies as $co): ?>
                        <option value="<?= $co['id'] ?>" <?= (($formData['company_id'] ?? 0) == $co['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($co['name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stage">Stage</label>
                <select id="stage" name="stage" class="form-control">
                    <?php foreach (['new','contacted','qualified','proposal','negotiation','won','lost'] as $s): ?>
                        <option value="<?= $s ?>" <?= (($formData['stage'] ?? 'new') === $s) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(crmLeadStageLabel($s), ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority" class="form-control">
                    <?php foreach (['low','medium','high','urgent'] as $p): ?>
                        <option value="<?= $p ?>" <?= (($formData['priority'] ?? 'medium') === $p) ? 'selected' : '' ?>>
                            <?= ucfirst($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="value">Deal Value</label>
                <input type="number" id="value" name="value" class="form-control"
                       value="<?= htmlspecialchars((string)($formData['value'] ?? 0), ENT_QUOTES) ?>"
                       min="0" step="0.01">
            </div>
            <div class="form-group">
                <label for="close_date">Expected Close Date</label>
                <input type="date" id="close_date" name="close_date" class="form-control"
                       value="<?= htmlspecialchars($formData['close_date'] ?? '', ENT_QUOTES) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="assigned_to">Assigned To</label>
                <select id="assigned_to" name="assigned_to" class="form-control">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (($formData['assigned_to'] ?? 0) == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="source">Lead Source</label>
                <input type="text" id="source" name="source" class="form-control"
                       value="<?= htmlspecialchars($formData['source'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="4"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Changes</button>
            <a href="<?= SITE_URL ?>/crm/leads/view.php?id=<?= $id ?>" class="btn btn--outline">Cancel</a>
            <?php if (crmCanDelete()): ?>
                <a href="<?= SITE_URL ?>/crm/leads/delete.php?id=<?= $id ?>&csrf=<?= crmCsrfToken() ?>"
                   class="btn btn--danger" style="margin-left:auto"
                   onclick="return confirm('Delete this lead?')">Delete</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
