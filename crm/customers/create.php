<?php
/**
 * crm/customers/create.php
 *
 * Create a new customer record.
 */

require_once dirname(__DIR__) . '/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireCRMEditor();

$db       = getCRMDB();
$errors   = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!crmValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {

        // Collect and sanitise field values.
        $formData = [
            'first_name'  => trim($_POST['first_name']  ?? ''),
            'last_name'   => trim($_POST['last_name']   ?? ''),
            'email'       => trim($_POST['email']       ?? ''),
            'phone'       => trim($_POST['phone']       ?? ''),
            'mobile'      => trim($_POST['mobile']      ?? ''),
            'job_title'   => trim($_POST['job_title']   ?? ''),
            'address'     => trim($_POST['address']     ?? ''),
            'city'        => trim($_POST['city']        ?? ''),
            'country'     => trim($_POST['country']     ?? ''),
            'status'      => trim($_POST['status']      ?? 'active'),
            'source'      => trim($_POST['source']      ?? ''),
            'company_id'  => (int) ($_POST['company_id'] ?? 0),
            'assigned_to' => (int) ($_POST['assigned_to'] ?? 0),
        ];

        // Basic validation.
        if ($formData['first_name'] === '' && $formData['last_name'] === '') {
            $errors[] = 'Please provide at least a first or last name.';
        }
        if ($formData['email'] !== '' && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The email address is not valid.';
        }
        $validStatuses = ['active','inactive','prospect','churned'];
        if (!in_array($formData['status'], $validStatuses, true)) {
            $formData['status'] = 'active';
        }

        if (empty($errors)) {
            $stmt = $db->prepare(
                "INSERT INTO crm_customers
                    (company_id, first_name, last_name, email, phone, mobile, job_title,
                     address, city, country, status, source, assigned_to, created_by)
                 VALUES
                    (:company_id, :first_name, :last_name, :email, :phone, :mobile, :job_title,
                     :address, :city, :country, :status, :source, :assigned_to, :created_by)"
            );
            $stmt->execute([
                ':company_id'  => $formData['company_id']  ?: null,
                ':first_name'  => $formData['first_name'],
                ':last_name'   => $formData['last_name'],
                ':email'       => $formData['email'],
                ':phone'       => $formData['phone'],
                ':mobile'      => $formData['mobile'],
                ':job_title'   => $formData['job_title'],
                ':address'     => $formData['address'],
                ':city'        => $formData['city'],
                ':country'     => $formData['country'],
                ':status'      => $formData['status'],
                ':source'      => $formData['source'],
                ':assigned_to' => $formData['assigned_to'] ?: null,
                ':created_by'  => (int) ($_SESSION['user_id'] ?? 0),
            ]);
            $newId = (int) $db->lastInsertId();

            // Handle tags (comma-separated string).
            $tagString = trim($_POST['tags'] ?? '');
            if ($tagString !== '') {
                crmSetTags('customer', $newId, array_map('trim', explode(',', $tagString)));
            }

            crmLogActivity('created', 'customer', $newId,
                $formData['first_name'] . ' ' . $formData['last_name']);

            crmFlash('Customer created successfully.', 'success');
            crmRedirect(CRM_URL . '/customers/view.php?id=' . $newId);
        }
    }
}

$companies = $db->query("SELECT id, name FROM crm_companies ORDER BY name ASC")->fetchAll();
$users     = crmGetUsers();

$pageTitle = 'New Customer';
$activeNav = 'customers';
require_once CRM_ROOT . '/templates/header.php';
?>

<div class="admin-header">
    <div>
        <h1>&#128100; New Customer</h1>
        <p><a href="<?= CRM_URL ?>/customers/">&#8592; Back to Customers</a></p>
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
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control"
                       value="<?= htmlspecialchars($formData['first_name'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control"
                       value="<?= htmlspecialchars($formData['last_name'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES) ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                       value="<?= htmlspecialchars($formData['phone'] ?? '', ENT_QUOTES) ?>" maxlength="30">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="mobile">Mobile</label>
                <input type="tel" id="mobile" name="mobile" class="form-control"
                       value="<?= htmlspecialchars($formData['mobile'] ?? '', ENT_QUOTES) ?>" maxlength="30">
            </div>
            <div class="form-group">
                <label for="job_title">Job Title</label>
                <input type="text" id="job_title" name="job_title" class="form-control"
                       value="<?= htmlspecialchars($formData['job_title'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_id">Company</label>
                <select id="company_id" name="company_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($companies as $co): ?>
                        <option value="<?= $co['id'] ?>"
                            <?= (($formData['company_id'] ?? 0) == $co['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($co['name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="assigned_to">Assigned To</label>
                <select id="assigned_to" name="assigned_to" class="form-control">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= (($formData['assigned_to'] ?? 0) == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <?php foreach (['active','inactive','prospect','churned'] as $s): ?>
                        <option value="<?= $s ?>" <?= (($formData['status'] ?? 'active') === $s) ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="source">Lead Source</label>
                <input type="text" id="source" name="source" class="form-control"
                       placeholder="e.g. Website, Referral, Event…"
                       value="<?= htmlspecialchars($formData['source'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" class="form-control"
                   value="<?= htmlspecialchars($formData['address'] ?? '', ENT_QUOTES) ?>" maxlength="255">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" class="form-control"
                       value="<?= htmlspecialchars($formData['city'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" class="form-control"
                       value="<?= htmlspecialchars($formData['country'] ?? '', ENT_QUOTES) ?>" maxlength="100">
            </div>
        </div>

        <div class="form-group">
            <label for="tags">Tags <small class="text-muted">(comma-separated)</small></label>
            <input type="text" id="tags" name="tags" class="form-control"
                   placeholder="e.g. VIP, Enterprise, UK"
                   value="<?= htmlspecialchars($_POST['tags'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Customer</button>
            <a href="<?= CRM_URL ?>/customers/" class="btn btn--outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once CRM_ROOT . '/templates/footer.php'; ?>
