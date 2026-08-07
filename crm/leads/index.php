<?php
/**
 * crm/leads/index.php
 *
 * Lead list view with pipeline stage filter, search, and pagination.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$flash = crmGetFlash();

$search  = trim($_GET['q']     ?? '');
$stage   = trim($_GET['stage'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]      = "(l.title LIKE :q OR l.contact_name LIKE :q OR l.contact_email LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
if ($stage !== '') {
    $where[]        = "l.stage = :stage";
    $params[':stage'] = $stage;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM crm_leads l {$whereClause}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pag = crmPaginate($total, $perPage, $page);
$params[':limit']  = $pag['per_page'];
$params[':offset'] = $pag['offset'];

$stmt = $db->prepare(
    "SELECT l.*,
            c.first_name || ' ' || c.last_name AS customer_name,
            co.name AS company_name,
            u.username AS assignee_name
     FROM crm_leads l
     LEFT JOIN crm_customers c  ON c.id  = l.customer_id
     LEFT JOIN crm_companies co ON co.id = l.company_id
     LEFT JOIN users u          ON u.id  = l.assigned_to
     {$whereClause}
     ORDER BY l.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Stage summary counts for the pipeline header
$stageCounts = $db->query(
    "SELECT stage, COUNT(*) AS cnt, SUM(value) AS total_value
     FROM crm_leads
     WHERE stage NOT IN ('won','lost')
     GROUP BY stage"
)->fetchAll(PDO::FETCH_ASSOC);
$stageMap = [];
foreach ($stageCounts as $s) {
    $stageMap[$s['stage']] = $s;
}

$pageTitle = 'Leads';
$activeNav = 'leads';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div>
        <h1>&#127919; Leads</h1>
        <p class="text-muted"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <?php if (crmCanEdit()): ?>
        <div class="admin-header__actions">
            <a href="<?= CRM_URL ?>/leads/create.php" class="btn btn--primary btn--sm">+ New Lead</a>
            <a href="<?= CRM_URL ?>/leads/export.php" class="btn btn--outline btn--sm">&#8659; Export CSV</a>
        </div>
    <?php endif; ?>
</div>

<!-- ── Pipeline stage summary ────────────────────────────────────── -->
<div class="crm-stage-bar">
    <?php foreach (['new','contacted','qualified','proposal','negotiation','won','lost'] as $s): ?>
        <a href="?stage=<?= $s ?>" class="crm-stage-bar__item <?= $stage === $s ? 'crm-stage-bar__item--active' : '' ?>">
            <span class="badge <?= crmLeadStageBadge($s) ?>"><?= htmlspecialchars(crmLeadStageLabel($s), ENT_QUOTES) ?></span>
            <span class="crm-stage-bar__count"><?= (int)($stageMap[$s]['cnt'] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
    <?php if ($stage): ?><a href="?" class="crm-stage-bar__item text-muted">Clear &#215;</a><?php endif; ?>
</div>

<!-- ── Search ────────────────────────────────────────────────────── -->
<form class="crm-filter-bar" method="get" action="">
    <?php if ($stage): ?>
        <input type="hidden" name="stage" value="<?= htmlspecialchars($stage, ENT_QUOTES) ?>">
    <?php endif; ?>
    <input type="search" name="q" class="form-control crm-filter-bar__search"
           placeholder="Search title, contact…" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
    <button type="submit" class="btn btn--primary btn--sm">Search</button>
    <?php if ($search): ?>
        <a href="<?= $stage ? '?stage=' . urlencode($stage) : '?' ?>" class="btn btn--outline btn--sm">Clear</a>
    <?php endif; ?>
</form>

<?php if ($leads): ?>
    <div class="crm-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Contact</th>
                    <th>Company</th>
                    <th>Stage</th>
                    <th>Value</th>
                    <th>Priority</th>
                    <th>Assigned To</th>
                    <th>Close Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l): ?>
                    <tr>
                        <td>
                            <a href="<?= CRM_URL ?>/leads/view.php?id=<?= $l['id'] ?>">
                                <?= htmlspecialchars($l['title'], ENT_QUOTES) ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $contact = $l['customer_name'] ?: $l['contact_name'];
                            echo htmlspecialchars($contact ?: '—', ENT_QUOTES);
                            ?>
                        </td>
                        <td><?= htmlspecialchars($l['company_name'] ?? '—', ENT_QUOTES) ?></td>
                        <td>
                            <span class="badge <?= crmLeadStageBadge($l['stage']) ?>">
                                <?= htmlspecialchars(crmLeadStageLabel($l['stage']), ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td>
                            <?= $l['value'] > 0
                                ? htmlspecialchars($l['currency'], ENT_QUOTES) . ' ' . number_format((float)$l['value'], 0)
                                : '—' ?>
                        </td>
                        <td>
                            <span class="badge badge--<?= htmlspecialchars($l['priority'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($l['priority'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($l['assignee_name'] ?? '—', ENT_QUOTES) ?></td>
                        <td class="text-muted"><?= $l['close_date'] ? crmFormatDate($l['close_date']) : '—' ?></td>
                        <td>
                            <div class="crm-table-actions">
                                <a href="<?= CRM_URL ?>/leads/view.php?id=<?= $l['id'] ?>" class="btn btn--outline btn--sm">View</a>
                                <?php if (crmCanEdit()): ?>
                                    <a href="<?= CRM_URL ?>/leads/edit.php?id=<?= $l['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                                <?php endif; ?>
                                <?php if (crmCanDelete()): ?>
                                    <a href="<?= CRM_URL ?>/leads/delete.php?id=<?= $l['id'] ?>&csrf=<?= crmCsrfToken() ?>"
                                       class="btn btn--danger btn--sm"
                                       onclick="return confirm('Delete this lead?')">Del</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= crmPaginationHtml($pag, '?', array_filter(['q' => $search, 'stage' => $stage])) ?>
<?php else: ?>
    <div class="empty-state">
        <h2>No leads found</h2>
        <?php if ($search || $stage): ?>
            <p><a href="?">Clear filters</a></p>
        <?php else: ?>
            <p><a href="<?= CRM_URL ?>/leads/create.php" class="btn btn--primary">Add your first lead</a></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
