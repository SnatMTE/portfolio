<?php
/**
 * crm/companies/index.php
 *
 * Company list with search, industry filter, and pagination.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$flash = crmGetFlash();

$search   = trim($_GET['q']        ?? '');
$industry = trim($_GET['industry'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 25;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]      = "(co.name LIKE :q OR co.website LIKE :q OR co.email LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
if ($industry !== '') {
    $where[]              = "co.industry = :industry";
    $params[':industry']  = $industry;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM crm_companies co {$whereClause}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pag = crmPaginate($total, $perPage, $page);
$params[':limit']  = $pag['per_page'];
$params[':offset'] = $pag['offset'];

$stmt = $db->prepare(
    "SELECT co.*,
            (SELECT COUNT(*) FROM crm_customers c WHERE c.company_id = co.id) AS customer_count,
            (SELECT COUNT(*) FROM crm_leads l    WHERE l.company_id  = co.id) AS lead_count
     FROM crm_companies co
     {$whereClause}
     ORDER BY co.name ASC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute($params);
$companies = $stmt->fetchAll();

$industries = $db->query("SELECT DISTINCT industry FROM crm_companies WHERE industry != '' ORDER BY industry ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Companies';
$activeNav = 'companies';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div>
        <h1>&#127970; Companies</h1>
        <p class="text-muted"><?= $total ?> compan<?= $total !== 1 ? 'ies' : 'y' ?></p>
    </div>
    <div class="admin-header__actions">
        <?php if (crmCanEdit()): ?>
            <a href="<?= CRM_URL ?>/companies/create.php" class="btn btn--primary btn--sm">+ New Company</a>
        <?php endif; ?>
        <?php if (crmCanEdit()): ?>
            <a href="<?= CRM_URL ?>/companies/export.php" class="btn btn--outline btn--sm">Export CSV</a>
        <?php endif; ?>
    </div>
</div>

<form class="crm-filter-bar" method="get" action="">
    <input type="search" name="q" class="form-control crm-filter-bar__search"
           placeholder="Search companies…" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
    <select name="industry" class="form-control" style="max-width:180px">
        <option value="">All Industries</option>
        <?php foreach ($industries as $ind): ?>
            <option value="<?= htmlspecialchars($ind, ENT_QUOTES) ?>" <?= $industry === $ind ? 'selected' : '' ?>>
                <?= htmlspecialchars($ind, ENT_QUOTES) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    <?php if ($search || $industry): ?>
        <a href="?" class="btn btn--outline btn--sm">Clear</a>
    <?php endif; ?>
</form>

<?php if ($companies): ?>
    <div class="crm-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Industry</th>
                    <th>Website</th>
                    <th>Customers</th>
                    <th>Leads</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $co): ?>
                    <tr>
                        <td class="crm-name-cell">
                            <a href="<?= CRM_URL ?>/companies/view.php?id=<?= $co['id'] ?>">
                                <?= htmlspecialchars($co['name'], ENT_QUOTES) ?>
                            </a>
                            <?php if ($co['city'] || $co['country']): ?>
                                <p class="text-muted" style="font-size:.8rem;margin:0">
                                    <?= htmlspecialchars(trim($co['city'] . ', ' . $co['country'], ', '), ENT_QUOTES) ?>
                                </p>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($co['industry'] ?: '—', ENT_QUOTES) ?></td>
                        <td>
                            <?php if ($co['website']): ?>
                                <a href="<?= htmlspecialchars($co['website'], ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= htmlspecialchars(preg_replace('#^https?://#', '', rtrim($co['website'], '/')), ENT_QUOTES) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $co['customer_count'] ?></td>
                        <td><?= $co['lead_count'] ?></td>
                        <td>
                            <div class="crm-table-actions">
                                <a href="<?= CRM_URL ?>/companies/view.php?id=<?= $co['id'] ?>" class="btn btn--outline btn--sm">View</a>
                                <?php if (crmCanEdit()): ?>
                                    <a href="<?= CRM_URL ?>/companies/edit.php?id=<?= $co['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                                <?php endif; ?>
                                <?php if (crmCanDelete()): ?>
                                    <a href="<?= CRM_URL ?>/companies/delete.php?id=<?= $co['id'] ?>&csrf=<?= crmCsrfToken() ?>"
                                       class="btn btn--danger btn--sm"
                                       onclick="return confirm('Delete company and unlink all contacts?')">Del</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= crmPaginationHtml($pag, '?', array_filter(['q' => $search, 'industry' => $industry])) ?>
<?php else: ?>
    <div class="empty-state">
        <h2>No companies found</h2>
        <?php if (crmCanEdit()): ?>
            <p><a href="<?= CRM_URL ?>/companies/create.php" class="btn btn--primary">Add a company</a></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
