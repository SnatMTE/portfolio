<?php
/**
 * crm/customers/index.php
 *
 * Customer list with search, status filter, and pagination.
 * Admins and editors see all customers; regular users see only their assigned ones.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db     = getCRMDB();
$uid    = (int) ($_SESSION['user_id'] ?? 0);
$flash  = crmGetFlash();
$isAdmin = cmsIsEditor(); // editors and above see everything

// ------------------------------------------------------------------
// Filters from query string
// ------------------------------------------------------------------
$search  = trim($_GET['q']      ?? '');
$status  = trim($_GET['status'] ?? '');
$company = (int) ($_GET['company'] ?? 0);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

// ------------------------------------------------------------------
// Build query
// ------------------------------------------------------------------
$where  = [];
$params = [];

if ($search !== '') {
    $where[]           = "(c.first_name LIKE :q OR c.last_name LIKE :q OR c.email LIKE :q OR c.phone LIKE :q)";
    $params[':q']      = '%' . $search . '%';
}
if ($status !== '') {
    $where[]           = "c.status = :status";
    $params[':status'] = $status;
}
if ($company > 0) {
    $where[]            = "c.company_id = :company";
    $params[':company'] = $company;
}
if (!$isAdmin) {
    // Non-editors only see their own assigned contacts.
    $where[]       = "c.assigned_to = :myuid";
    $params[':myuid'] = $uid;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) $db->prepare(
    "SELECT COUNT(*) FROM crm_customers c {$whereClause}"
)->execute($params) ? $db->prepare(
    "SELECT COUNT(*) FROM crm_customers c {$whereClause}"
)->execute($params) ?: 0 : 0;

// Re-run the count properly.
$countStmt = $db->prepare("SELECT COUNT(*) FROM crm_customers c {$whereClause}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pag    = crmPaginate($total, $perPage, $page);
$params[':limit']  = $pag['per_page'];
$params[':offset'] = $pag['offset'];

$stmt = $db->prepare(
    "SELECT c.*, co.name AS company_name, u.username AS assignee_name
     FROM crm_customers c
     LEFT JOIN crm_companies co ON co.id = c.company_id
     LEFT JOIN users u ON u.id = c.assigned_to
     {$whereClause}
     ORDER BY c.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Companies for filter dropdown
$companies = $db->query("SELECT id, name FROM crm_companies ORDER BY name ASC")->fetchAll();

$pageTitle = 'Customers';
$activeNav = 'customers';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div>
        <h1>&#128100; Customers</h1>
        <p class="text-muted"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <?php if (crmCanEdit()): ?>
        <div class="admin-header__actions">
            <a href="<?= SITE_URL ?>/crm/customers/create.php" class="btn btn--primary btn--sm">+ New Customer</a>
            <a href="<?= SITE_URL ?>/crm/customers/export.php<?= $search ? '?q=' . urlencode($search) : '' ?>"
               class="btn btn--outline btn--sm">&#8659; Export CSV</a>
        </div>
    <?php endif; ?>
</div>

<!-- ── Filter bar ────────────────────────────────────────────────── -->
<form class="crm-filter-bar" method="get" action="">
    <input type="search" name="q" class="form-control crm-filter-bar__search"
           placeholder="Search name, email, phone…" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">

    <select name="status" class="form-control crm-filter-bar__select">
        <option value="">All statuses</option>
        <?php foreach (['active','inactive','prospect','churned'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="company" class="form-control crm-filter-bar__select">
        <option value="">All companies</option>
        <?php foreach ($companies as $co): ?>
            <option value="<?= $co['id'] ?>" <?= $company === (int)$co['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($co['name'], ENT_QUOTES) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    <?php if ($search || $status || $company): ?>
        <a href="?" class="btn btn--outline btn--sm">Clear</a>
    <?php endif; ?>
</form>

<!-- ── Table ─────────────────────────────────────────────────────── -->
<?php if ($customers): ?>
    <div class="crm-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <div class="crm-name-cell">
                                <div class="crm-avatar crm-avatar--sm">
                                    <?= strtoupper(substr($c['first_name'], 0, 1) . substr($c['last_name'], 0, 1)) ?>
                                </div>
                                <a href="<?= SITE_URL ?>/crm/customers/view.php?id=<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'], ENT_QUOTES) ?>
                                </a>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($c['email'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($c['phone'], ENT_QUOTES) ?></td>
                        <td>
                            <?php if ($c['company_name']): ?>
                                <a href="<?= SITE_URL ?>/crm/companies/view.php?id=<?= $c['company_id'] ?>">
                                    <?= htmlspecialchars($c['company_name'], ENT_QUOTES) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= crmStatusBadge($c['status']) ?>">
                                <?= htmlspecialchars($c['status'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($c['assignee_name'] ?? '—', ENT_QUOTES) ?></td>
                        <td class="text-muted"><?= crmFormatDate($c['created_at']) ?></td>
                        <td>
                            <div class="crm-table-actions">
                                <a href="<?= SITE_URL ?>/crm/customers/view.php?id=<?= $c['id'] ?>"
                                   class="btn btn--outline btn--sm">View</a>
                                <?php if (crmCanEdit()): ?>
                                    <a href="<?= SITE_URL ?>/crm/customers/edit.php?id=<?= $c['id'] ?>"
                                       class="btn btn--outline btn--sm">Edit</a>
                                <?php endif; ?>
                                <?php if (crmCanDelete()): ?>
                                    <a href="<?= SITE_URL ?>/crm/customers/delete.php?id=<?= $c['id'] ?>&csrf=<?= crmCsrfToken() ?>"
                                       class="btn btn--danger btn--sm"
                                       onclick="return confirm('Delete this customer? This cannot be undone.')">Del</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= crmPaginationHtml($pag, '?', array_filter(['q' => $search, 'status' => $status, 'company' => $company ?: null])) ?>

<?php else: ?>
    <div class="empty-state">
        <h2>No customers found</h2>
        <p>
            <?php if ($search || $status): ?>
                Try adjusting your filters, or <a href="?">clear them</a>.
            <?php else: ?>
                <a href="<?= SITE_URL ?>/crm/customers/create.php" class="btn btn--primary">Add your first customer</a>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
