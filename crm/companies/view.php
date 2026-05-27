<?php
/**
 * crm/companies/view.php
 *
 * Company detail page: linked customers, leads, notes, and activity timeline.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMAccess();

$db    = getCRMDB();
$id    = (int) ($_GET['id'] ?? 0);
$flash = crmGetFlash();

if ($id <= 0) { crmRedirect(CRM_URL . '/companies/'); }

$stmt = $db->prepare(
    "SELECT co.*, u.username AS assignee_name, cb.username AS created_by_name
     FROM crm_companies co
     LEFT JOIN users u  ON u.id  = co.assigned_to
     LEFT JOIN users cb ON cb.id = co.created_by
     WHERE co.id = :id"
);
$stmt->execute([':id' => $id]);
$co = $stmt->fetch();
if (!$co) { http_response_code(404); exit('Company not found.'); }

$customers = $db->prepare(
    "SELECT id, first_name || ' ' || last_name AS name, job_title, status
     FROM crm_customers WHERE company_id = :id ORDER BY last_name ASC"
);
$customers->execute([':id' => $id]);
$customers = $customers->fetchAll();

$leads = $db->prepare(
    "SELECT id, title, stage, value, currency FROM crm_leads WHERE company_id = :id ORDER BY created_at DESC"
);
$leads->execute([':id' => $id]);
$leads = $leads->fetchAll();

$notes = $db->prepare(
    "SELECT n.*, u.username AS author FROM crm_notes n
     LEFT JOIN users u ON u.id = n.created_by
     WHERE n.related_type = 'company' AND n.related_id = :id ORDER BY n.created_at DESC"
);
$notes->execute([':id' => $id]);
$notes = $notes->fetchAll();

$activity = $db->prepare(
    "SELECT a.*, u.username FROM crm_activity_log a LEFT JOIN users u ON u.id = a.user_id
     WHERE a.entity_type = 'company' AND a.entity_id = :id ORDER BY a.created_at DESC LIMIT 20"
);
$activity->execute([':id' => $id]);
$activity = $activity->fetchAll();

$pageTitle = htmlspecialchars($co['name'], ENT_QUOTES);
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
        <h1>&#127970; <?= htmlspecialchars($co['name'], ENT_QUOTES) ?></h1>
        <?php if ($co['industry']): ?>
            <p class="text-muted"><?= htmlspecialchars($co['industry'], ENT_QUOTES) ?></p>
        <?php endif; ?>
    </div>
    <div class="admin-header__actions">
        <?php if (crmCanEdit()): ?>
            <a href="<?= CRM_URL ?>/companies/edit.php?id=<?= $id ?>" class="btn btn--primary btn--sm">Edit</a>
        <?php endif; ?>
        <a href="<?= CRM_URL ?>/companies/" class="btn btn--outline btn--sm">&#8592; Back</a>
    </div>
</div>

<div class="crm-profile-grid">
    <div class="crm-profile-sidebar">
        <div class="crm-panel">
            <div class="crm-panel__header"><h2>Details</h2></div>
            <dl class="crm-dl">
                <?php if ($co['website']): ?>
                    <dt>Website</dt>
                    <dd><a href="<?= htmlspecialchars($co['website'], ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars(preg_replace('#^https?://#', '', rtrim($co['website'], '/')), ENT_QUOTES) ?>
                    </a></dd>
                <?php endif; ?>
                <?php if ($co['phone']): ?><dt>Phone</dt><dd><?= htmlspecialchars($co['phone'], ENT_QUOTES) ?></dd><?php endif; ?>
                <?php if ($co['email']): ?><dt>Email</dt><dd><a href="mailto:<?= htmlspecialchars($co['email'], ENT_QUOTES) ?>"><?= htmlspecialchars($co['email'], ENT_QUOTES) ?></a></dd><?php endif; ?>
                <?php if ($co['address'] || $co['city'] || $co['country']): ?>
                    <dt>Address</dt>
                    <dd><?= htmlspecialchars(implode(', ', array_filter([$co['address'], $co['city'], $co['country']])), ENT_QUOTES) ?></dd>
                <?php endif; ?>
                <dt>Assigned To</dt><dd><?= htmlspecialchars($co['assignee_name'] ?? '—', ENT_QUOTES) ?></dd>
                <dt>Created</dt><dd><?= crmFormatDate($co['created_at'], 'j M Y') ?></dd>
            </dl>
        </div>
        <?php if ($co['notes']): ?>
            <div class="crm-panel">
                <div class="crm-panel__header"><h2>Notes</h2></div>
                <p><?= nl2br(htmlspecialchars($co['notes'], ENT_QUOTES)) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="crm-profile-main">
        <!-- Customers -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128100; Contacts (<?= count($customers) ?>)</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/customers/create.php?company_id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add</a>
                <?php endif; ?>
            </div>
            <?php if ($customers): ?>
                <ul class="crm-recent-list">
                    <?php foreach ($customers as $c): ?>
                        <li class="crm-recent-list__item">
                            <a href="<?= CRM_URL ?>/customers/view.php?id=<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
                            </a>
                            <?php if ($c['job_title']): ?><span class="text-muted">— <?= htmlspecialchars($c['job_title'], ENT_QUOTES) ?></span><?php endif; ?>
                            <span class="badge <?= crmStatusBadge($c['status']) ?>"><?= htmlspecialchars($c['status'], ENT_QUOTES) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No contacts linked.</p>
            <?php endif; ?>
        </section>

        <!-- Leads -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#127919; Leads (<?= count($leads) ?>)</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/leads/create.php?company_id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add</a>
                <?php endif; ?>
            </div>
            <?php if ($leads): ?>
                <ul class="crm-recent-list">
                    <?php foreach ($leads as $l): ?>
                        <li class="crm-recent-list__item">
                            <a href="<?= CRM_URL ?>/leads/view.php?id=<?= $l['id'] ?>"><?= htmlspecialchars($l['title'], ENT_QUOTES) ?></a>
                            <span class="badge <?= crmLeadStageBadge($l['stage']) ?>"><?= htmlspecialchars(crmLeadStageLabel($l['stage']), ENT_QUOTES) ?></span>
                            <?php if ($l['value'] > 0): ?>
                                <span class="text-muted"><?= htmlspecialchars($l['currency'], ENT_QUOTES) ?> <?= number_format((float)$l['value'], 0) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted crm-empty-state">No leads linked.</p>
            <?php endif; ?>
        </section>

        <!-- Notes -->
        <section class="crm-panel">
            <div class="crm-panel__header">
                <h2>&#128203; Notes</h2>
                <?php if (crmCanEdit()): ?>
                    <a href="<?= CRM_URL ?>/notes/create.php?type=company&id=<?= $id ?>" class="btn btn--primary btn--sm">+ Add Note</a>
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
                <p class="text-muted crm-empty-state">No notes yet.</p>
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
