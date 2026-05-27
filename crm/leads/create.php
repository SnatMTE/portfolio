<?php
/**
 * crm/leads/create.php
 *
 * Create a new lead record.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db       = getCRMDB();
$errors   = [];
$formData = [
    'stage'    => 'new',
    'priority' => 'medium',
    'currency' => 'GBP',
];

// Pre-fill customer_id if coming from a customer profile link.
$preCustomerId = (int) ($_GET['customer_id'] ?? 0);
if ($preCustomerId > 0) {
    $formData['customer_id'] = $preCustomerId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
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

        if ($formData['title'] === '') {
            $errors[] = 'Lead title is required.';
        }
        $validStages    = ['new','contacted','qualified','proposal','negotiation','won','lost'];
        $validPriority  = ['low','medium','high','urgent'];
        if (!in_array($formData['stage'], $validStages, true))   $formData['stage']    = 'new';
        if (!in_array($formData['priority'], $validPriority, true)) $formData['priority'] = 'medium';
        if ($formData['contact_email'] !== '' && !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The contact email address is not valid.';
        }
        $closeDate = null;
        if ($formData['close_date'] !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $formData['close_date']);
            $closeDate = $dt ? $dt->format('Y-m-d') : null;
        }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO crm_leads
                    (title, customer_id, company_id, contact_name, contact_email, contact_phone,
                     value, currency, stage, priority, source, close_date, assigned_to, notes, created_by)
                 VALUES
                    (:title, :customer_id, :company_id, :contact_name, :contact_email, :contact_phone,
                     :value, :currency, :stage, :priority, :source, :close_date, :assigned_to, :notes, :created_by)"
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
                ':created_by'    => (int) ($_SESSION['user_id'] ?? 0),
            ]);
            $newId = (int) $db->lastInsertId();

            crmLogActivity('created', 'lead', $newId, $formData['title']);
            crmFlash('Lead created successfully.', 'success');
            crmRedirect(SITE_URL . '/crm/leads/view.php?id=' . $newId);
        }
    }
}

$customers = $db->query("SELECT id, first_name || ' ' || last_name AS name FROM crm_customers ORDER BY last_name ASC")->fetchAll();
$companies = $db->query("SELECT id, name FROM crm_companies ORDER BY name ASC")->fetchAll();
$users     = crmGetUsers();

$pageTitle = 'New Lead';
$activeNav = 'leads';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#127919; New Lead</h1>
        <p><a href="<?= SITE_URL ?>/crm/leads/">&#8592; Back to Leads</a></p>
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
            <label for="title">Lead Title <span class="text-muted">(required)</span></label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?= htmlspecialchars($formData['title'] ?? '', ENT_QUOTES) ?>" maxlength="255" required>
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
                <label for="contact_name">Contact Name</label>
                <input type="text" id="contact_name" name="contact_name" class="form-control"
                       value="<?= htmlspecialchars($formData['contact_name'] ?? '', ENT_QUOTES) ?>" maxlength="150">
            </div>
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       value="<?= htmlspecialchars($formData['contact_email'] ?? '', ENT_QUOTES) ?>" maxlength="255">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stage">Pipeline Stage</label>
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
                <label for="currency">Currency</label>
                <select id="currency" name="currency" class="form-control">
                    <?php foreach (['GBP','USD','EUR','CAD','AUD'] as $cur): ?>
                        <option value="<?= $cur ?>" <?= (($formData['currency'] ?? 'GBP') === $cur) ? 'selected' : '' ?>>
                            <?= $cur ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="close_date">Expected Close Date</label>
                <input type="date" id="close_date" name="close_date" class="form-control"
                       value="<?= htmlspecialchars($formData['close_date'] ?? '', ENT_QUOTES) ?>">
            </div>
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
        </div>

        <div class="form-group">
            <label for="source">Lead Source</label>
            <input type="text" id="source" name="source" class="form-control"
                   value="<?= htmlspecialchars($formData['source'] ?? '', ENT_QUOTES) ?>" maxlength="100">
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="4"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Lead</button>
            <a href="<?= SITE_URL ?>/crm/leads/" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
