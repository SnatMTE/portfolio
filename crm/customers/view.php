<?php
/**
 * crm/customers/view.php
 *
 * Customer profile page — shows contact details, linked company, notes,
 * attached tasks, leads, tags, activity timeline, and follow-ups.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$id    = (int) ($_GET['id'] ?? 0);
$flash = crmGetFlash();

if ($id <= 0) {
    crmRedirect(CRM_URL . '/customers/');
}

$stmt = $db->prepare(
    "SELECT c.*, co.name AS company_name, u.username AS assignee_name
     FROM crm_customers c
     LEFT JOIN crm_companies co ON co.id = c.company_id
     LEFT JOIN users u ON u.id = c.assigned_to
     WHERE c.id = :id"
);
$stmt->execute([':id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(404);
    exit('Customer not found.');
}

// Tags
$tags = crmGetTags('customer', $id);

// Notes
$notes = $db->prepare(
    "SELECT n.*, u.username AS author
     FROM crm_notes n
     LEFT JOIN users u ON u.id = n.created_by
     WHERE n.related_type = 'customer' AND n.related_id = :id
     ORDER BY n.created_at DESC"
);
$notes->execute([':id' => $id]);
$notes = $notes->fetchAll();

// Tasks linked to this customer
$tasks = $db->prepare(
    "SELECT t.*, u.username AS assignee_name
     FROM crm_tasks t
     LEFT JOIN users u ON u.id = t.assigned_to
     WHERE t.related_type = 'customer' AND t.related_id = :id
     ORDER BY t.status = 'completed', t.due_date ASC
     LIMIT 20"
);
$tasks->execute([':id' => $id]);
$tasks = $tasks->fetchAll();

// Leads linked to this customer
$leads = $db->prepare(
    "SELECT * FROM crm_leads WHERE customer_id = :id ORDER BY created_at DESC LIMIT 10"
);
$leads->execute([':id' => $id]);
$leads = $leads->fetchAll();

// Activity log for this customer
$activity = $db->prepare(
    "SELECT a.*, u.username
     FROM crm_activity_log a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.entity_type = 'customer' AND a.entity_id = :id
     ORDER BY a.created_at DESC
     LIMIT 20"
);
$activity->execute([':id' => $id]);
$activity = $activity->fetchAll();

// Upcoming follow-ups
$followUps = $db->prepare(
    "SELECT * FROM crm_follow_ups
     WHERE related_type = 'customer' AND related_id = :id AND is_done = 0
     ORDER BY due_at ASC"
);
$followUps->execute([':id' => $id]);
$followUps = $followUps->fetchAll();

$fullName  = trim($customer['first_name'] . ' ' . $customer['last_name']);
$pageTitle = $fullName ?: 'Customer';
$activeNav = 'customers';
require_once CRM_ROOT . '/templates/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>" role="alert">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<div class="admin-header">
    <div class="crm-profile-header">
        <div class="crm-avatar crm-avatar--lg">
            <?= strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)) ?>
        </div>
        <div>
            <h1><?= htmlspecialchars($fullName, ENT_QUOTES) ?></h1>
            <p class="text-muted">
                <?php if ($customer['job_title']): ?>
                    <?= htmlspecialchars($customer['job_title'], ENT_QUOTES) ?>
                <?php endif; ?>
                <?php if ($customer['company_name']): ?>
                    <?php if ($customer['job_title']): ?> at <?php endif; ?>
                    <a href="<?= CRM_URL ?>/companies/view.php?id=<?= $customer['company_id'] ?>">
                        <?= htmlspecialchars($customer['company_name'], ENT_QUOTES) ?>
                    </a>
                <?php endif; ?>
            </p>
            <div class="crm-profile-tags">
                <span class="badge <?= crmStatusBadge($customer['status']) ?>"><?= htmlspecialchars($customer['status'], ENT_QUOTES) ?></span>
                <?php foreach ($tags as $tag): ?>
                    <span class="tag-pill" style="border-color:<?= htmlspecialchars($tag['colour'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars($tag['name'], ENT_QUOTES) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="admin-header__actions">
        <?php if (crmCanEdit()): ?>
            <a href="<?= CRM_URL ?>/customers/edit.php?id=<?= $id ?>" class="btn btn--primary btn--sm">Edit</a>
        <?php endif; ?>
        <a href="<?= CRM_URL ?>/customers/" class="btn btn--outline btn--sm">&#8592; Back</a>
    </div>
</div>

<div class="crm-profile-grid">

    <!-- Left: contact details + follow-ups -->
    <div class="crm-profile-sidebar">
        <div class="crm-panel">
            <div class="crm-panel__header"><h2>Contact Details</h2></div>
            <dl class="crm-dl">
                <dt>Email</dt>
                <dd><?= $customer['email'] ? '<a href="mailto:' . htmlspecialchars($customer['email'], ENT_QUOTES) . '">' . htmlspecialchars($customer['email'], ENT_QUOTES) . '</a>' : '—' ?></dd>
                <dt>Phone</dt>
                <dd><?= htmlspecialchars($customer['phone'] ?: '—', ENT_QUOTES) ?></dd>
                <dt>Mobile</dt>
                <dd><?= htmlspecialchars($customer['mobile'] ?: '—', ENT_QUOTES) ?></dd>
                <dt>City</dt>
                <dd><?= htmlspecialchars($customer['city'] ?: '—', ENT_QUOTES) ?></dd>
                <dt>Country</dt>
                <dd><?= htmlspecialchars($customer['country'] ?: '—', ENT_QUOTES) ?></dd>
                <dt>Source</dt>
                <dd><?= htmlspecialchars($customer['source'] ?: '—', ENT_QUOTES) ?></dd>
                <dt>Assigned To</dt>
                <dd><?= htmlspecialchars($customer['assignee_name'] ?? '—', ENT_QUOTES) ?></dd>
                <dt>Added</dt>
                <dd><?= crmFormatDate($customer['created_at'], 'j M Y') ?></dd>
            </dl>
        </div>

        <!-- Quick actions -->
        <?php if (crmCanEdit()): ?>
        <div class="crm-panel">
            <div class="crm-panel__header"><h2>Quick Actions</h2></div>
            <div class="crm-quick-actions">
                <a href="<?= CRM_URL ?>/tasks/create.php?customer_id=<?= $id ?>" class="btn btn--outline btn--sm btn--full">
                    + Add Task
                </a>
                <a href="<?= CRM_URL ?>/leads/create.php?customer_id=<?= $id ?>" class="btn btn--outline btn--sm btn--full">
                    + Create Lead
                </a>
                <a href="<?= CRM_URL ?>/notes/create.php?type=customer&id=<?= $id ?>" class="btn btn--outline btn--sm btn--full">
                    + Add Note
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Follow-ups -->
        <?php if ($followUps): ?>
        <div class="crm-panel">
            <div class="crm-panel__header"><h2>&#128222; Follow-Ups</h2></div>
            <ul class="crm-task-list">
                <?php foreach ($followUps as $fu): ?>
                    <li class="crm-task-list__item">
                        <span><?= crmFormatDate($fu['due_at'], 'j M, H:i') ?></span>
                        <?php if ($fu['note']): ?>
                            <span class="text-muted">— <?= htmlspecialchars($fu['note'], ENT_QUOTES) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div><!-- /sidebar -->

    <!-- Right: notes, tasks, leads, activity -->
    <div class="crm-profile-main">

        <!-- Notes -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128203; Notes</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/notes/create.php?type=customer&id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add Note</a>
                <?php endif; ?>
            </div>
            <?php if ($notes): ?>
                <ol class="crm-notes-list">
                    <?php foreach ($notes as $note): ?>
                        <li class="crm-notes-list__item <?= $note['is_private'] ? 'crm-notes-list__item--private' : '' ?>">
                            <div class="crm-notes-list__meta">
                                <strong><?= htmlspecialchars($note['author'] ?? 'Unknown', ENT_QUOTES) ?></strong>
                                <time class="text-muted"><?= crmTimeAgo($note['created_at']) ?></time>
                                <?php if ($note['is_private']): ?>
                                    <span class="badge badge--muted">Private</span>
                                <?php endif; ?>
                                <?php if (crmCanEdit()): ?>
                                    <a href="<?= CRM_URL ?>/notes/delete.php?id=<?= $note['id'] ?>&csrf=<?= crmCsrfToken() ?>"
                                       class="crm-notes-list__delete text-muted"
                                       onclick="return confirm('Delete this note?')">&#10005;</a>
                                <?php endif; ?>
                            </div>
                            <div class="crm-notes-list__content">
                                <?= nl2br(htmlspecialchars($note['content'], ENT_QUOTES)) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No notes yet.</p>
            <?php endif; ?>
        </section>

        <!-- Tasks -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#9989; Tasks</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/tasks/create.php?customer_id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add Task</a>
                <?php endif; ?>
            </div>
            <?php if ($tasks): ?>
                <ul class="crm-task-list">
                    <?php foreach ($tasks as $t): ?>
                        <li class="crm-task-list__item">
                            <a href="<?= CRM_URL ?>/tasks/edit.php?id=<?= $t['id'] ?>" class="crm-task-list__title">
                                <?= htmlspecialchars($t['title'], ENT_QUOTES) ?>
                            </a>
                            <span class="badge <?= crmStatusBadge($t['status']) ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', $t['status']), ENT_QUOTES) ?>
                            </span>
                            <?php if ($t['due_date']): ?>
                                <span class="text-muted"><?= crmFormatDate($t['due_date']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No tasks linked to this customer.</p>
            <?php endif; ?>
        </section>

        <!-- Leads -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#127919; Leads</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/leads/create.php?customer_id=<?= $id ?>" class="btn btn--primary btn--sm">+ New Lead</a>
                <?php endif; ?>
            </div>
            <?php if ($leads): ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Title</th><th>Stage</th><th>Value</th><th>Close Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $l): ?>
                            <tr>
                                <td><a href="<?= CRM_URL ?>/leads/view.php?id=<?= $l['id'] ?>"><?= htmlspecialchars($l['title'], ENT_QUOTES) ?></a></td>
                                <td><span class="badge <?= crmLeadStageBadge($l['stage']) ?>"><?= htmlspecialchars(crmLeadStageLabel($l['stage']), ENT_QUOTES) ?></span></td>
                                <td><?= $l['value'] > 0 ? htmlspecialchars($l['currency'], ENT_QUOTES) . ' ' . number_format((float)$l['value'], 2) : '—' ?></td>
                                <td class="text-muted"><?= $l['close_date'] ? crmFormatDate($l['close_date']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No leads linked to this customer.</p>
            <?php endif; ?>
        </section>

        <!-- Activity timeline -->
        <section class="crm-panel">
            <div class="crm-panel__header"><h2>&#8987; Activity Timeline</h2></div>
            <?php if ($activity): ?>
                <ol class="crm-activity-feed crm-activity-feed--timeline">
                    <?php foreach ($activity as $act): ?>
                        <li class="crm-activity-feed__item">
                            <span class="crm-activity-feed__who"><?= htmlspecialchars($act['username'] ?? 'System', ENT_QUOTES) ?></span>
                            <span class="crm-activity-feed__action"><?= htmlspecialchars($act['action'], ENT_QUOTES) ?></span>
                            <?php if ($act['description']): ?>
                                <span class="text-muted">— <?= htmlspecialchars($act['description'], ENT_QUOTES) ?></span>
                            <?php endif; ?>
                            <time class="crm-activity-feed__time text-muted"><?= crmFormatDate($act['created_at'], 'j M Y, H:i') ?></time>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No activity recorded yet.</p>
            <?php endif; ?>
        </section>

    </div><!-- /main -->
</div><!-- /.crm-profile-grid -->

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
