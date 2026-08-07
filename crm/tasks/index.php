<?php
/**
 * crm/tasks/index.php
 *
 * Task list with status filter, overdue highlighting, and search.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$uid   = (int) ($_SESSION['user_id'] ?? 0);
$flash = crmGetFlash();

$search  = trim($_GET['q']      ?? '');
$status  = trim($_GET['status'] ?? '');
$filter  = trim($_GET['filter'] ?? '');  // 'today', 'overdue', 'mine'
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]      = "t.title LIKE :q";
    $params[':q'] = '%' . $search . '%';
}
if ($status !== '') {
    $where[]           = "t.status = :status";
    $params[':status'] = $status;
}
if ($filter === 'today') {
    $where[] = "date(t.due_date) = date('now')";
} elseif ($filter === 'overdue') {
    $where[] = "t.due_date < datetime('now') AND t.status NOT IN ('completed','cancelled')";
} elseif ($filter === 'mine') {
    $where[]         = "t.assigned_to = :myuid";
    $params[':myuid'] = $uid;
}

// Non-editors only see their own tasks.
if (!cmsIsEditor()) {
    $where[]          = "t.assigned_to = :myuid2";
    $params[':myuid2'] = $uid;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM crm_tasks t {$whereClause}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pag = crmPaginate($total, $perPage, $page);
$params[':limit']  = $pag['per_page'];
$params[':offset'] = $pag['offset'];

$stmt = $db->prepare(
    "SELECT t.*, u.username AS assignee_name
     FROM crm_tasks t
     LEFT JOIN users u ON u.id = t.assigned_to
     {$whereClause}
     ORDER BY
       CASE t.status WHEN 'completed' THEN 1 WHEN 'cancelled' THEN 1 ELSE 0 END,
       CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END,
       t.due_date ASC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$pageTitle = 'Tasks';
$activeNav = 'tasks';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div>
        <h1>&#9989; Tasks</h1>
        <p class="text-muted"><?= $total ?> task<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <?php if (crmCanEdit()): ?>
        <a href="<?= CRM_URL ?>/tasks/create.php" class="btn btn--primary btn--sm">+ New Task</a>
    <?php endif; ?>
</div>

<!-- ── Quick filter tabs ─────────────────────────────────────────── -->
<div class="crm-stage-bar">
    <a href="?" class="crm-stage-bar__item <?= !$filter && !$status ? 'crm-stage-bar__item--active' : '' ?>">All</a>
    <a href="?filter=today"   class="crm-stage-bar__item <?= $filter === 'today'   ? 'crm-stage-bar__item--active' : '' ?>">Due Today</a>
    <a href="?filter=overdue" class="crm-stage-bar__item <?= $filter === 'overdue' ? 'crm-stage-bar__item--active' : '' ?>">Overdue</a>
    <a href="?filter=mine"    class="crm-stage-bar__item <?= $filter === 'mine'    ? 'crm-stage-bar__item--active' : '' ?>">Mine</a>
    <a href="?status=pending"     class="crm-stage-bar__item <?= $status === 'pending'     ? 'crm-stage-bar__item--active' : '' ?>">Pending</a>
    <a href="?status=in_progress" class="crm-stage-bar__item <?= $status === 'in_progress' ? 'crm-stage-bar__item--active' : '' ?>">In Progress</a>
    <a href="?status=completed"   class="crm-stage-bar__item <?= $status === 'completed'   ? 'crm-stage-bar__item--active' : '' ?>">Completed</a>
</div>

<form class="crm-filter-bar" method="get" action="">
    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter, ENT_QUOTES) ?>"><?php endif; ?>
    <?php if ($status): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES) ?>"><?php endif; ?>
    <input type="search" name="q" class="form-control crm-filter-bar__search"
           placeholder="Search tasks…" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
    <button type="submit" class="btn btn--primary btn--sm">Search</button>
</form>

<?php if ($tasks): ?>
    <div class="crm-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Related</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $t): ?>
                    <?php
                    $isOverdue = $t['due_date'] && $t['due_date'] < date('Y-m-d H:i:s')
                        && !in_array($t['status'], ['completed', 'cancelled']);
                    ?>
                    <tr <?= $isOverdue ? 'class="crm-row--overdue"' : '' ?>>
                        <td>
                            <a href="<?= CRM_URL ?>/tasks/edit.php?id=<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['title'], ENT_QUOTES) ?>
                            </a>
                            <?php if ($t['description']): ?>
                                <p class="text-muted" style="font-size:.8rem;margin:0">
                                    <?= htmlspecialchars(mb_strimwidth($t['description'], 0, 60, '…'), ENT_QUOTES) ?>
                                </p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= crmStatusBadge($t['status']) ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', $t['status']), ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge--<?= htmlspecialchars($t['priority'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($t['priority'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td class="<?= $isOverdue ? 'crm-cell--danger' : 'text-muted' ?>">
                            <?= $t['due_date'] ? crmFormatDate($t['due_date'], 'j M Y') : '—' ?>
                            <?= $isOverdue ? '<span class="badge badge--danger" style="margin-left:.35rem">Overdue</span>' : '' ?>
                        </td>
                        <td>
                            <?php if ($t['related_type'] && $t['related_id']): ?>
                                <a href="<?= CRM_URL ?>/<?= htmlspecialchars($t['related_type'], ENT_QUOTES) ?>s/view.php?id=<?= $t['related_id'] ?>">
                                    <?= ucfirst(htmlspecialchars($t['related_type'], ENT_QUOTES)) ?> #<?= $t['related_id'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($t['assignee_name'] ?? '—', ENT_QUOTES) ?></td>
                        <td>
                            <div class="crm-table-actions">
                                <?php if (crmCanEdit()): ?>
                                    <a href="<?= CRM_URL ?>/tasks/edit.php?id=<?= $t['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                                <?php endif; ?>
                                <?php if (crmCanDelete()): ?>
                                    <a href="<?= CRM_URL ?>/tasks/delete.php?id=<?= $t['id'] ?>&csrf=<?= crmCsrfToken() ?>"
                                       class="btn btn--danger btn--sm"
                                       onclick="return confirm('Delete this task?')">Del</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= crmPaginationHtml($pag, '?', array_filter(['q' => $search, 'status' => $status, 'filter' => $filter])) ?>
<?php else: ?>
    <div class="empty-state">
        <h2>No tasks found</h2>
        <?php if (crmCanEdit()): ?>
            <p><a href="<?= CRM_URL ?>/tasks/create.php" class="btn btn--primary">Add a task</a></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
