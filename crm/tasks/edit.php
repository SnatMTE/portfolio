<?php
/**
 * crm/tasks/edit.php
 *
 * Edit an existing task.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db     = getCRMDB();
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

if ($id <= 0) { crmRedirect(SITE_URL . '/crm/tasks/'); }

$stmt = $db->prepare("SELECT * FROM crm_tasks WHERE id = :id");
$stmt->execute([':id' => $id]);
$task = $stmt->fetch();
if (!$task) { http_response_code(404); exit('Task not found.'); }

$formData = $task;

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
                "UPDATE crm_tasks SET
                    title        = :title,
                    description  = :desc,
                    related_type = :rtype,
                    related_id   = :rid,
                    status       = :status,
                    priority     = :priority,
                    due_date     = :due,
                    assigned_to  = :assigned,
                    updated_at   = datetime('now')
                 WHERE id = :id"
            )->execute([
                ':title'    => $formData['title'],
                ':desc'     => $formData['description'],
                ':rtype'    => $formData['related_type'] ?: null,
                ':rid'      => $formData['related_id']   ?: null,
                ':status'   => $formData['status'],
                ':priority' => $formData['priority'],
                ':due'      => $dueDate,
                ':assigned' => $formData['assigned_to'] ?: null,
                ':id'       => $id,
            ]);
            crmLogActivity('updated', 'task', $id, $formData['title']);
            crmFlash('Task updated.', 'success');

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

// Format datetime for the input field.
$dueDateInput = '';
if ($formData['due_date']) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $formData['due_date']);
    $dueDateInput = $dt ? $dt->format('Y-m-d\TH:i') : substr($formData['due_date'], 0, 16);
}

$pageTitle = 'Edit Task';
$activeNav = 'tasks';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#9989; Edit Task</h1>
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
            <label for="title">Task Title</label>
            <input type="text" id="title" name="title" class="form-control" required
                   value="<?= htmlspecialchars($formData['title'] ?? '', ENT_QUOTES) ?>" maxlength="255">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($formData['description'] ?? '', ENT_QUOTES) ?></textarea>
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
                       value="<?= htmlspecialchars($dueDateInput, ENT_QUOTES) ?>">
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
            <button type="submit" class="btn btn--primary">Save Changes</button>
            <a href="<?= SITE_URL ?>/crm/tasks/" class="btn btn--outline">Cancel</a>
            <?php if (crmCanDelete()): ?>
                <a href="<?= SITE_URL ?>/crm/tasks/delete.php?id=<?= $id ?>&csrf=<?= crmCsrfToken() ?>"
                   class="btn btn--danger" style="margin-left:auto"
                   onclick="return confirm('Delete this task?')">Delete</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
