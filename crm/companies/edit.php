<?php
/**
 * crm/companies/edit.php
 *
 * Edit an existing company record.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db     = getCRMDB();
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

if ($id <= 0) { crmRedirect(SITE_URL . '/crm/companies/'); }

$stmt = $db->prepare("SELECT * FROM crm_companies WHERE id = :id");
$stmt->execute([':id' => $id]);
$co = $stmt->fetch();
if (!$co) { http_response_code(404); exit('Company not found.'); }

$formData = $co;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $formData = [
            'name'        => trim($_POST['name']        ?? ''),
            'industry'    => trim($_POST['industry']    ?? ''),
            'website'     => trim($_POST['website']     ?? ''),
            'phone'       => trim($_POST['phone']       ?? ''),
            'email'       => trim($_POST['email']       ?? ''),
            'address'     => trim($_POST['address']     ?? ''),
            'city'        => trim($_POST['city']        ?? ''),
            'country'     => trim($_POST['country']     ?? ''),
            'notes'       => trim($_POST['notes']       ?? ''),
            'assigned_to' => (int) ($_POST['assigned_to'] ?? 0),
        ];

        if ($formData['name'] === '') { $errors[] = 'Company name is required.'; }
        if ($formData['email'] !== '' && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        }
        if ($formData['website'] !== '' && !filter_var($formData['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Website URL is not valid.';
        }

        if (empty($errors)) {
            $db->prepare(
                "UPDATE crm_companies SET
                    name        = :name,
                    industry    = :industry,
                    website     = :website,
                    phone       = :phone,
                    email       = :email,
                    address     = :address,
                    city        = :city,
                    country     = :country,
                    notes       = :notes,
                    assigned_to = :assigned,
                    updated_at  = datetime('now')
                 WHERE id = :id"
            )->execute([
                ':name'     => $formData['name'],
                ':industry' => $formData['industry'],
                ':website'  => $formData['website'],
                ':phone'    => $formData['phone'],
                ':email'    => $formData['email'],
                ':address'  => $formData['address'],
                ':city'     => $formData['city'],
                ':country'  => $formData['country'],
                ':notes'    => $formData['notes'],
                ':assigned' => $formData['assigned_to'] ?: null,
                ':id'       => $id,
            ]);
            crmLogActivity('updated', 'company', $id, $formData['name']);
            crmFlash('Company updated.', 'success');
            crmRedirect(SITE_URL . '/crm/companies/view.php?id=' . $id);
        }
    }
}

$users = crmGetUsers();

$pageTitle = 'Edit Company';
$activeNav = 'companies';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#127970; Edit Company</h1>
        <p><a href="<?= SITE_URL ?>/crm/companies/view.php?id=<?= $id ?>">&#8592; Back</a></p>
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

        <div class="form-row">
            <div class="form-group">
                <label for="name">Company Name</label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?= htmlspecialchars($formData['name'] ?? '', ENT_QUOTES) ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label for="industry">Industry</label>
                <input type="text" id="industry" name="industry" class="form-control"
                       value="<?= htmlspecialchars($formData['industry'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="website">Website</label>
                <input type="url" id="website" name="website" class="form-control"
                       value="<?= htmlspecialchars($formData['website'] ?? '', ENT_QUOTES) ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                       value="<?= htmlspecialchars($formData['phone'] ?? '', ENT_QUOTES) ?>" maxlength="50">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES) ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" class="form-control"
                       value="<?= htmlspecialchars($formData['city'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" class="form-control"
                       value="<?= htmlspecialchars($formData['address'] ?? '', ENT_QUOTES) ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" class="form-control"
                       value="<?= htmlspecialchars($formData['country'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
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

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="4"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Changes</button>
            <a href="<?= SITE_URL ?>/crm/companies/view.php?id=<?= $id ?>" class="btn btn--outline">Cancel</a>
            <?php if (crmCanDelete()): ?>
                <a href="<?= SITE_URL ?>/crm/companies/delete.php?id=<?= $id ?>&csrf=<?= crmCsrfToken() ?>"
                   class="btn btn--danger" style="margin-left:auto"
                   onclick="return confirm('Delete this company?')">Delete</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
