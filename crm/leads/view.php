<?php
/**
 * crm/leads/view.php
 *
 * Lead detail page: shows all lead fields, linked customer/company,
 * attached notes, tasks, and the activity timeline.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$id    = (int) ($_GET['id'] ?? 0);
$flash = crmGetFlash();

if ($id <= 0) { crmRedirect(CRM_URL . '/leads/'); }

$stmt = $db->prepare(
    "SELECT l.*,
            c.first_name || ' ' || c.last_name AS customer_name,
            co.name AS company_name,
            u.username AS assignee_name,
            cb.username AS created_by_name
     FROM crm_leads l
     LEFT JOIN crm_customers c  ON c.id  = l.customer_id
     LEFT JOIN crm_companies co ON co.id = l.company_id
     LEFT JOIN users u          ON u.id  = l.assigned_to
     LEFT JOIN users cb         ON cb.id = l.created_by
     WHERE l.id = :id"
);
$stmt->execute([':id' => $id]);
$lead = $stmt->fetch();

if (!$lead) { http_response_code(404); exit('Lead not found.'); }

$notes = $db->prepare(
    "SELECT n.*, u.username AS author
     FROM crm_notes n
     LEFT JOIN users u ON u.id = n.created_by
     WHERE n.related_type = 'lead' AND n.related_id = :id
     ORDER BY n.created_at DESC"
);
$notes->execute([':id' => $id]);
$notes = $notes->fetchAll();

$tasks = $db->prepare(
    "SELECT t.*, u.username AS assignee_name
     FROM crm_tasks t
     LEFT JOIN users u ON u.id = t.assigned_to
     WHERE t.related_type = 'lead' AND t.related_id = :id
     ORDER BY t.status = 'completed', t.due_date ASC"
);
$tasks->execute([':id' => $id]);
$tasks = $tasks->fetchAll();

$activity = $db->prepare(
    "SELECT a.*, u.username FROM crm_activity_log a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.entity_type = 'lead' AND a.entity_id = :id
     ORDER BY a.created_at DESC LIMIT 20"
);
$activity->execute([':id' => $id]);
$activity = $activity->fetchAll();

$pageTitle = htmlspecialchars($lead['title'], ENT_QUOTES);
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
        <h1>&#127919; <?= htmlspecialchars($lead['title'], ENT_QUOTES) ?></h1>
        <div style="display:flex;gap:.75rem;align-items:center;margin-top:.25rem">
            <span class="badge <?= crmLeadStageBadge($lead['stage']) ?>"><?= htmlspecialchars(crmLeadStageLabel($lead['stage']), ENT_QUOTES) ?></span>
            <span class="badge badge--<?= htmlspecialchars($lead['priority'], ENT_QUOTES) ?>"><?= ucfirst($lead['priority']) ?></span>
        </div>
    </div>
    <div class="admin-header__actions">
        <?php if (crmCanEdit()): ?>
            <a href="<?= CRM_URL ?>/leads/edit.php?id=<?= $id ?>" class="btn btn--primary btn--sm">Edit</a>
        <?php endif; ?>
        <a href="<?= CRM_URL ?>/leads/" class="btn btn--outline btn--sm">&#8592; Back</a>
    </div>
</div>

<div class="crm-profile-grid">
    <div class="crm-profile-sidebar">
        <div class="crm-panel">
            <div class="crm-panel__header"><h2>Lead Details</h2></div>
            <dl class="crm-dl">
                <dt>Value</dt>
                <dd><?= $lead['value'] > 0 ? htmlspecialchars($lead['currency'], ENT_QUOTES) . ' ' . number_format((float)$lead['value'], 2) : '—' ?></dd>
                <dt>Expected Close</dt>
                <dd><?= $lead['close_date'] ? crmFormatDate($lead['close_date']) : '—' ?></dd>
                <dt>Source</dt>
                <dd><?= htmlspecialchars($lead['source'] ?: '—', ENT_QUOTES) ?></dd>
                <dt>Customer</dt>
                <dd>
                    <?php if ($lead['customer_id'] && $lead['customer_name']): ?>
                        <a href="<?= CRM_URL ?>/customers/view.php?id=<?= $lead['customer_id'] ?>">
                            <?= htmlspecialchars($lead['customer_name'], ENT_QUOTES) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($lead['contact_name'] ?: '—', ENT_QUOTES) ?>
                    <?php endif; ?>
                </dd>
                <dt>Company</dt>
                <dd>
                    <?php if ($lead['company_id'] && $lead['company_name']): ?>
                        <a href="<?= CRM_URL ?>/companies/view.php?id=<?= $lead['company_id'] ?>">
                            <?= htmlspecialchars($lead['company_name'], ENT_QUOTES) ?>
                        </a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </dd>
                <dt>Assigned To</dt>
                <dd><?= htmlspecialchars($lead['assignee_name'] ?? '—', ENT_QUOTES) ?></dd>
                <dt>Created By</dt>
                <dd><?= htmlspecialchars($lead['created_by_name'] ?? '—', ENT_QUOTES) ?></dd>
                <dt>Created</dt>
                <dd><?= crmFormatDate($lead['created_at'], 'j M Y') ?></dd>
            </dl>
        </div>
        <?php if ($lead['notes']): ?>
        <div class="crm-panel">
            <div class="crm-panel__header"><h2>Lead Note</h2></div>
            <p><?= nl2br(htmlspecialchars($lead['notes'], ENT_QUOTES)) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="crm-profile-main">
        <!-- Notes -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128203; Notes</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/notes/create.php?type=lead&id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add Note</a>
                <?php endif; ?>
            </div>
            <?php if ($notes): ?>
                <ol class="crm-notes-list">
                    <?php foreach ($notes as $note): ?>
                        <li class="crm-notes-list__item">
                            <div class="crm-notes-list__meta">
                                <strong><?= htmlspecialchars($note['author'] ?? 'Unknown', ENT_QUOTES) ?></strong>
                                <time class="text-muted"><?= crmTimeAgo($note['created_at']) ?></time>
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
                <p class="text-muted crm-empty-state">No notes on this lead.</p>
            <?php endif; ?>
        </section>

        <!-- Tasks -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#9989; Tasks</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/tasks/create.php?lead_id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add Task</a>
                <?php endif; ?>
            </div>
            <?php if ($tasks): ?>
                <ul class="crm-task-list">
                    <?php foreach ($tasks as $t): ?>
                        <li class="crm-task-list__item">
                            <a href="<?= CRM_URL ?>/tasks/edit.php?id=<?= $t['id'] ?>"><?= htmlspecialchars($t['title'], ENT_QUOTES) ?></a>
                            <span class="badge <?= crmStatusBadge($t['status']) ?>"><?= htmlspecialchars(str_replace('_',' ',$t['status']), ENT_QUOTES) ?></span>
                            <?php if ($t['due_date']): ?><span class="text-muted"><?= crmFormatDate($t['due_date']) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No tasks linked.</p>
            <?php endif; ?>
        </section>

        <!-- Activity -->
        <section class="crm-panel">
            <div class="crm-panel__header"><h2>&#8987; Activity</h2></div>
            <?php if ($activity): ?>
                <ol class="crm-activity-feed">
                    <?php foreach ($activity as $act): ?>
                        <li class="crm-activity-feed__item">
                            <span class="crm-activity-feed__who"><?= htmlspecialchars($act['username'] ?? 'System', ENT_QUOTES) ?></span>
                            <span class="crm-activity-feed__action"><?= htmlspecialchars($act['action'], ENT_QUOTES) ?></span>
                            <?php if ($act['description']): ?><span class="text-muted">— <?= htmlspecialchars($act['description'], ENT_QUOTES) ?></span><?php endif; ?>
                            <time class="crm-activity-feed__time text-muted"><?= crmFormatDate($act['created_at'], 'j M Y, H:i') ?></time>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No activity yet.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
