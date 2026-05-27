<?php
/**
 * crm/activity.php
 *
 * Paginated activity log for the entire CRM.
 * Filters: user, entity_type, date range.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireCRMAccess();

$db  = getCRMDB();
$uid = (int) ($_SESSION['user_id'] ?? 0);

$filterUser   = (int) ($_GET['user_id']     ?? 0);
$filterType   = trim($_GET['entity_type']   ?? '');
$filterFrom   = trim($_GET['from']          ?? '');
$filterTo     = trim($_GET['to']            ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 50;

$where  = [];
$params = [];

if ($filterUser > 0) {
    $where[]            = "a.user_id = :user_id";
    $params[':user_id'] = $filterUser;
}
if ($filterType !== '') {
    $where[]               = "a.entity_type = :etype";
    $params[':etype']      = $filterType;
}
if ($filterFrom !== '') {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $filterFrom);
    if ($dt) {
        $where[]          = "date(a.created_at) >= :from";
        $params[':from']  = $dt->format('Y-m-d');
    }
}
if ($filterTo !== '') {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $filterTo);
    if ($dt) {
        $where[]          = "date(a.created_at) <= :to";
        $params[':to']    = $dt->format('Y-m-d');
    }
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM crm_activity_log a {$whereClause}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pag = crmPaginate($total, $perPage, $page);
$params[':limit']  = $pag['per_page'];
$params[':offset'] = $pag['offset'];

$stmt = $db->prepare(
    "SELECT a.*, u.username
     FROM crm_activity_log a
     LEFT JOIN users u ON u.id = a.user_id
     {$whereClause}
     ORDER BY a.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute($params);
$entries = $stmt->fetchAll();

$users     = crmGetUsers();
$typesList = ['customer','lead','company','task','note','message'];

$pageTitle = 'Activity Log';
$activeNav = 'activity';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#8987; Activity Log</h1>
        <p class="text-muted"><?= $total ?> entr<?= $total !== 1 ? 'ies' : 'y' ?></p>
    </div>
</div>

<form class="crm-filter-bar" method="get" action="">
    <select name="user_id" class="form-control" style="max-width:160px">
        <option value="">All Users</option>
        <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterUser === (int) $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="entity_type" class="form-control" style="max-width:160px">
        <option value="">All Types</option>
        <?php foreach ($typesList as $t): ?>
            <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                <?= ucfirst($t) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="from" class="form-control" style="max-width:150px"
           value="<?= htmlspecialchars($filterFrom, ENT_QUOTES) ?>" title="From date">
    <input type="date" name="to" class="form-control" style="max-width:150px"
           value="<?= htmlspecialchars($filterTo, ENT_QUOTES) ?>" title="To date">
    <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    <?php if ($filterUser || $filterType || $filterFrom || $filterTo): ?>
        <a href="activity.php" class="btn btn--outline btn--sm">Clear</a>
    <?php endif; ?>
</form>

<?php if ($entries): ?>
    <div class="crm-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Type</th>
                    <th>Record</th>
                    <th>Details</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['username'] ?? 'System', ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($e['action'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($e['entity_type'] ?? '—', ENT_QUOTES) ?></td>
                        <td>
                            <?php if ($e['entity_type'] && $e['entity_id']): ?>
                                <a href="<?= CRM_URL ?>/<?= htmlspecialchars($e['entity_type'], ENT_QUOTES) ?>s/view.php?id=<?= $e['entity_id'] ?>">
                                    #<?= $e['entity_id'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($e['description'] ?? '', ENT_QUOTES) ?></td>
                        <td class="text-muted" title="<?= htmlspecialchars($e['created_at'], ENT_QUOTES) ?>">
                            <?= crmTimeAgo($e['created_at']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= crmPaginationHtml($pag, 'activity.php?', array_filter([
        'user_id'     => $filterUser  ?: '',
        'entity_type' => $filterType,
        'from'        => $filterFrom,
        'to'          => $filterTo,
    ])) ?>
<?php else: ?>
    <div class="empty-state"><h2>No activity found</h2></div>
<?php endif; ?>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
