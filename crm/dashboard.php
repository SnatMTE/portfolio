<?php
/**
 * crm/dashboard.php  (also served as crm/index.php)
 *
 * CRM dashboard — the first page staff see after navigating to /crm/.
 * Shows aggregate statistics, tasks due today, recent activity,
 * open leads by stage, and quick-action buttons.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$uid   = (int) ($_SESSION['user_id'] ?? 0);
$stats = getCRMStats();
$flash = crmGetFlash();

// ------------------------------------------------------------------
// Tasks due today (assigned to current user or all if admin)
// ------------------------------------------------------------------
$taskQuery = cmsIsAdmin()
    ? "SELECT t.*, u.username AS assignee_name
       FROM crm_tasks t
       LEFT JOIN users u ON u.id = t.assigned_to
       WHERE date(t.due_date) = date('now')
         AND t.status NOT IN ('completed','cancelled')
       ORDER BY t.priority DESC, t.due_date ASC
       LIMIT 10"
    : "SELECT t.*, u.username AS assignee_name
       FROM crm_tasks t
       LEFT JOIN users u ON u.id = t.assigned_to
       WHERE date(t.due_date) = date('now')
         AND t.status NOT IN ('completed','cancelled')
         AND t.assigned_to = :uid
       ORDER BY t.priority DESC, t.due_date ASC
       LIMIT 10";

$taskStmt = $db->prepare($taskQuery);
if (!cmsIsAdmin()) {
    $taskStmt->execute([':uid' => $uid]);
} else {
    $taskStmt->execute();
}
$tasksDueToday = $taskStmt->fetchAll();

// ------------------------------------------------------------------
// Recent activity (last 15 entries)
// ------------------------------------------------------------------
$actStmt = $db->prepare(
    "SELECT a.*, u.username
     FROM crm_activity_log a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 15"
);
$actStmt->execute();
$recentActivity = $actStmt->fetchAll();

// ------------------------------------------------------------------
// Open leads by pipeline stage
// ------------------------------------------------------------------
$stageStmt = $db->prepare(
    "SELECT stage, COUNT(*) AS cnt, SUM(value) AS total_value
     FROM crm_leads
     WHERE stage NOT IN ('won','lost')
     GROUP BY stage
     ORDER BY CASE stage
       WHEN 'new'         THEN 1
       WHEN 'contacted'   THEN 2
       WHEN 'qualified'   THEN 3
       WHEN 'proposal'    THEN 4
       WHEN 'negotiation' THEN 5
       ELSE 6 END"
);
$stageStmt->execute();
$leadsByStage = $stageStmt->fetchAll();

// ------------------------------------------------------------------
// Recent customers (last 5)
// ------------------------------------------------------------------
$recentCustomers = $db->query(
    "SELECT id, first_name, last_name, email, status, created_at
     FROM crm_customers
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

// ------------------------------------------------------------------
// Upcoming follow-ups (next 7 days, assigned to current user)
// ------------------------------------------------------------------
$followUpStmt = $db->prepare(
    "SELECT f.*, 
            CASE f.related_type
              WHEN 'customer' THEN (SELECT first_name || ' ' || last_name FROM crm_customers WHERE id = f.related_id)
              WHEN 'lead'     THEN (SELECT title FROM crm_leads WHERE id = f.related_id)
              ELSE ''
            END AS related_name
     FROM crm_follow_ups f
     WHERE f.is_done = 0
       AND f.due_at BETWEEN datetime('now') AND datetime('now', '+7 days')
       AND (f.assigned_to = :uid OR :uid2 IN (SELECT id FROM users WHERE role_id = 1))
     ORDER BY f.due_at ASC
     LIMIT 8"
);
$followUpStmt->execute([':uid' => $uid, ':uid2' => $uid]);
$followUps = $followUpStmt->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div>
        <h1>Dashboard</h1>
        <p class="text-muted">Welcome back, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES) ?></strong></p>
    </div>
    <div class="admin-header__actions">
        <a href="<?= SITE_URL ?>/crm/customers/create.php" class="btn btn--primary btn--sm">+ New Customer</a>
        <a href="<?= SITE_URL ?>/crm/leads/create.php"     class="btn btn--outline btn--sm">+ New Lead</a>
        <a href="<?= SITE_URL ?>/crm/tasks/create.php"     class="btn btn--outline btn--sm">+ New Task</a>
    </div>
</div>

<!-- ── Stats cards ─────────────────────────────────────────────────── -->
<div class="stats-grid crm-stats-grid">
    <div class="stat-card">
        <span class="stat-card__number"><?= $stats['total_customers'] ?></span>
        <span class="stat-card__label">Total Customers</span>
        <a href="<?= SITE_URL ?>/crm/customers/" class="stat-card__link">View all</a>
    </div>
    <div class="stat-card">
        <span class="stat-card__number"><?= $stats['open_leads'] ?></span>
        <span class="stat-card__label">Open Leads</span>
        <a href="<?= SITE_URL ?>/crm/leads/" class="stat-card__link">View pipeline</a>
    </div>
    <div class="stat-card stat-card--<?= $stats['tasks_today'] > 0 ? 'warning' : 'ok' ?>">
        <span class="stat-card__number"><?= $stats['tasks_today'] ?></span>
        <span class="stat-card__label">Tasks Due Today</span>
        <a href="<?= SITE_URL ?>/crm/tasks/" class="stat-card__link">View tasks</a>
    </div>
    <div class="stat-card stat-card--<?= $stats['tasks_overdue'] > 0 ? 'danger' : 'ok' ?>">
        <span class="stat-card__number"><?= $stats['tasks_overdue'] ?></span>
        <span class="stat-card__label">Overdue Tasks</span>
        <a href="<?= SITE_URL ?>/crm/tasks/?filter=overdue" class="stat-card__link">View overdue</a>
    </div>
    <div class="stat-card">
        <span class="stat-card__number"><?= $stats['total_companies'] ?></span>
        <span class="stat-card__label">Companies</span>
        <a href="<?= SITE_URL ?>/crm/companies/" class="stat-card__link">View all</a>
    </div>
    <div class="stat-card <?= $stats['unread_messages'] > 0 ? 'stat-card--info' : '' ?>">
        <span class="stat-card__number"><?= $stats['unread_messages'] ?></span>
        <span class="stat-card__label">Unread Messages</span>
        <a href="<?= SITE_URL ?>/crm/messages/" class="stat-card__link">View inbox</a>
    </div>
</div>

<!-- ── Main two-column layout ──────────────────────────────────────── -->
<div class="crm-dashboard-grid">

    <!-- Left column -->
    <div class="crm-dashboard-col">

        <!-- Tasks due today -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#9989; Tasks Due Today</h2>
                <a href="<?= SITE_URL ?>/crm/tasks/create.php" class="btn btn--primary btn--sm">+ Add Task</a>
            </div>
            <?php if ($tasksDueToday): ?>
                <ul class="crm-task-list">
                    <?php foreach ($tasksDueToday as $t): ?>
                        <li class="crm-task-list__item crm-task-list__item--<?= htmlspecialchars($t['priority'], ENT_QUOTES) ?>">
                            <a href="<?= SITE_URL ?>/crm/tasks/edit.php?id=<?= $t['id'] ?>" class="crm-task-list__title">
                                <?= htmlspecialchars($t['title'], ENT_QUOTES) ?>
                            </a>
                            <span class="badge <?= crmStatusBadge($t['status']) ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', $t['status']), ENT_QUOTES) ?>
                            </span>
                            <?php if ($t['assignee_name']): ?>
                                <span class="crm-task-list__assignee text-muted">
                                    &#128100; <?= htmlspecialchars($t['assignee_name'], ENT_QUOTES) ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No tasks due today. &#127881;</p>
            <?php endif; ?>
        </section>

        <!-- Lead pipeline summary -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#127919; Lead Pipeline</h2>
                <a href="<?= SITE_URL ?>/crm/leads/" class="btn btn--outline btn--sm">View all</a>
            </div>
            <?php if ($leadsByStage): ?>
                <div class="crm-pipeline-bars">
                    <?php foreach ($leadsByStage as $s): ?>
                        <div class="crm-pipeline-bar">
                            <span class="crm-pipeline-bar__label">
                                <?= htmlspecialchars(crmLeadStageLabel($s['stage']), ENT_QUOTES) ?>
                            </span>
                            <span class="crm-pipeline-bar__count badge <?= crmLeadStageBadge($s['stage']) ?>">
                                <?= (int)$s['cnt'] ?>
                            </span>
                            <?php if ($s['total_value'] > 0): ?>
                                <span class="crm-pipeline-bar__value text-muted">
                                    £<?= number_format((float)$s['total_value'], 0) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No open leads in the pipeline.</p>
            <?php endif; ?>
        </section>

        <!-- Upcoming follow-ups -->
        <?php if ($followUps): ?>
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128222; Upcoming Follow-Ups</h2>
            </div>
            <ul class="crm-task-list">
                <?php foreach ($followUps as $fu): ?>
                    <li class="crm-task-list__item">
                        <span class="crm-task-list__title">
                            <?= htmlspecialchars($fu['related_name'] ?: 'Unknown', ENT_QUOTES) ?>
                        </span>
                        <span class="text-muted"><?= crmFormatDate($fu['due_at'], 'j M, H:i') ?></span>
                        <?php if ($fu['note']): ?>
                            <span class="text-muted crm-task-list__note">
                                — <?= htmlspecialchars(mb_strimwidth($fu['note'], 0, 60, '…'), ENT_QUOTES) ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

    </div><!-- /left column -->

    <!-- Right column -->
    <div class="crm-dashboard-col">

        <!-- Recent customers -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128100; Recent Customers</h2>
                <a href="<?= SITE_URL ?>/crm/customers/" class="btn btn--outline btn--sm">View all</a>
            </div>
            <?php if ($recentCustomers): ?>
                <ul class="crm-recent-list">
                    <?php foreach ($recentCustomers as $c): ?>
                        <li class="crm-recent-list__item">
                            <div class="crm-avatar">
                                <?= strtoupper(substr($c['first_name'], 0, 1) . substr($c['last_name'], 0, 1)) ?>
                            </div>
                            <div class="crm-recent-list__body">
                                <a href="<?= SITE_URL ?>/crm/customers/view.php?id=<?= $c['id'] ?>" class="crm-recent-list__name">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'], ENT_QUOTES) ?>
                                </a>
                                <span class="text-muted"><?= htmlspecialchars($c['email'], ENT_QUOTES) ?></span>
                            </div>
                            <span class="badge <?= crmStatusBadge($c['status']) ?>">
                                <?= htmlspecialchars($c['status'], ENT_QUOTES) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No customers yet.</p>
            <?php endif; ?>
        </section>

        <!-- Recent activity feed -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128203; Recent Activity</h2>
                <a href="<?= SITE_URL ?>/crm/activity.php" class="btn btn--outline btn--sm">Full log</a>
            </div>
            <?php if ($recentActivity): ?>
                <ol class="crm-activity-feed">
                    <?php foreach ($recentActivity as $act): ?>
                        <li class="crm-activity-feed__item">
                            <span class="crm-activity-feed__who">
                                <?= htmlspecialchars($act['username'] ?? 'System', ENT_QUOTES) ?>
                            </span>
                            <span class="crm-activity-feed__action">
                                <?= htmlspecialchars($act['action'], ENT_QUOTES) ?>
                                <?= $act['entity_type'] ? htmlspecialchars($act['entity_type'], ENT_QUOTES) : '' ?>
                            </span>
                            <?php if ($act['description']): ?>
                                <span class="crm-activity-feed__desc text-muted">
                                    — <?= htmlspecialchars(mb_strimwidth($act['description'], 0, 80, '…'), ENT_QUOTES) ?>
                                </span>
                            <?php endif; ?>
                            <time class="crm-activity-feed__time text-muted" datetime="<?= $act['created_at'] ?>">
                                <?= crmTimeAgo($act['created_at']) ?>
                            </time>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No activity recorded yet.</p>
            <?php endif; ?>
        </section>

    </div><!-- /right column -->

</div><!-- /.crm-dashboard-grid -->

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
