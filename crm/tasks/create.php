<?php
/**
 * crm/tasks/create.php
 *
 * Create a new task. Accepts ?lead_id= or ?customer_id= for pre-fill.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db     = getCRMDB();
$errors = [];

$preLeadId     = (int) ($_GET['lead_id']     ?? 0);
$preCustomerId = (int) ($_GET['customer_id'] ?? 0);

$formData = [
    'status'       => 'pending',
    'priority'     => 'medium',
    'related_type' => $preLeadId ? 'lead' : ($preCustomerId ? 'customer' : ''),
    'related_id'   => $preLeadId ?: ($preCustomerId ?: 0),
    'assigned_to'  => (int) ($_SESSION['user_id'] ?? 0),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $formData = [
            'title'        => trim($_POST['title']        ?? ''),
            'description'  => trim($_POST['description']  ?? ''),
            'related_type' => trim($_POST['related_type'] ?? ''),
            'related_id'   => (int) ($_POST['related_id'] ?? 0),
            'status'       => trim($_POST['status']       ?? 'pending'),
            'priority'     => trim($_POST['priority']     ?? 'medium'),
            'due_date'     => trim($_POST['due_date']     ?? ''),
            'assigned_to'  => (int) ($_POST['assigned_to'] ?? 0),
        ];

        if ($formData['title'] === '') { $errors[] = 'Task title is required.'; }

        $validStatus   = ['pending','in_progress','completed','cancelled'];
        $validPriority = ['low','medium','high','urgent'];
        if (!in_array($formData['status'],   $validStatus,   true)) $formData['status']   = 'pending';
        if (!in_array($formData['priority'], $validPriority, true)) $formData['priority'] = 'medium';

        $dueDate = null;
        if ($formData['due_date'] !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $formData['due_date']);
            $dueDate = $dt ? $dt->format('Y-m-d H:i:s') : null;
        }

        if (empty($errors)) {
            $db->prepare(
                "INSERT INTO crm_tasks
                    (title, description, related_type, related_id, status, priority, due_date, assigned_to, created_by)
                 VALUES
                    (:title, :desc, :rtype, :rid, :status, :priority, :due, :assigned, :created)"
            )->execute([
                ':title'    => $formData['title'],
                ':desc'     => $formData['description'],
                ':rtype'    => $formData['related_type'] ?: null,
                ':rid'      => $formData['related_id']   ?: null,
                ':status'   => $formData['status'],
                ':priority' => $formData['priority'],
                ':due'      => $dueDate,
                ':assigned' => $formData['assigned_to'] ?: null,
                ':created'  => (int) ($_SESSION['user_id'] ?? 0),
            ]);
            $newId = (int) $db->lastInsertId();
            crmLogActivity('created', 'task', $newId, $formData['title']);
            crmFlash('Task created.', 'success');

            // Return to the parent entity if one was linked.
            if ($formData['related_type'] && $formData['related_id']) {
                crmRedirect(SITE_URL . '/crm/' . $formData['related_type'] . 's/view.php?id=' . $formData['related_id']);
            }
            crmRedirect(SITE_URL . '/crm/tasks/');
        }
    }
}

$customers = $db->query("SELECT id, first_name || ' ' || last_name AS name FROM crm_customers ORDER BY last_name ASC")->fetchAll();
$leads     = $db->query("SELECT id, title AS name FROM crm_leads ORDER BY title ASC")->fetchAll();
$companies = $db->query("SELECT id, name FROM crm_companies ORDER BY name ASC")->fetchAll();
$users     = crmGetUsers();

$pageTitle = 'New Task';
$activeNav = 'tasks';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#9989; New Task</h1>
        <p><a href="<?= SITE_URL ?>/crm/tasks/">&#8592; Back to Tasks</a></p>
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
            <label for="title">Task Title <span class="text-muted">(required)</span></label>
            <input type="text" id="title" name="title" class="form-control" required
                   value="<?= htmlspecialchars($formData['title'] ?? '', ENT_QUOTES) ?>" maxlength="255">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($formData['description'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="related_type">Link To</label>
                <select id="related_type" name="related_type" class="form-control" onchange="crmUpdateRelatedList(this.value)">
                    <option value="">— Nothing —</option>
                    <option value="customer" <?= (($formData['related_type'] ?? '') === 'customer') ? 'selected' : '' ?>>Customer</option>
                    <option value="lead"     <?= (($formData['related_type'] ?? '') === 'lead')     ? 'selected' : '' ?>>Lead</option>
                    <option value="company"  <?= (($formData['related_type'] ?? '') === 'company')  ? 'selected' : '' ?>>Company</option>
                </select>
            </div>
            <div class="form-group">
                <label for="related_id">Record</label>
                <select id="related_id" name="related_id" class="form-control">
                    <option value="">— Select above first —</option>
                    <?php
                    $relType = $formData['related_type'] ?? '';
                    $relId   = (int) ($formData['related_id'] ?? 0);
                    $lists   = ['customer' => $customers, 'lead' => $leads, 'company' => $companies];
                    if ($relType && isset($lists[$relType])) {
                        foreach ($lists[$relType] as $item) {
                            $sel = $relId === $item['id'] ? 'selected' : '';
                            echo '<option value="' . $item['id'] . '" ' . $sel . '>' . htmlspecialchars($item['name'], ENT_QUOTES) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <?php foreach (['pending','in_progress','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= (($formData['status'] ?? 'pending') === $s) ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
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
                <label for="due_date">Due Date &amp; Time</label>
                <input type="datetime-local" id="due_date" name="due_date" class="form-control"
                       value="<?= htmlspecialchars(isset($formData['due_date']) && $formData['due_date'] ? substr($formData['due_date'],0,16) : '', ENT_QUOTES) ?>">
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

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Task</button>
            <a href="<?= SITE_URL ?>/crm/tasks/" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<script>
// Populate the related_id dropdown when related_type changes.
// Data is embedded so no AJAX needed.
(function () {
    var lists = {
        customer: <?= json_encode(array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name']], $customers)) ?>,
        lead:     <?= json_encode(array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name']], $leads)) ?>,
        company:  <?= json_encode(array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name']], $companies)) ?>,
    };
    window.crmUpdateRelatedList = function(type) {
        var sel = document.getElementById('related_id');
        sel.innerHTML = '<option value="">— Select —</option>';
        if (!lists[type]) return;
        lists[type].forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            sel.appendChild(opt);
        });
    };
})();
</script>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
