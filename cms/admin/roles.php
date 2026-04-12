<?php
/**
 * cms/admin/roles.php  —  View CMS roles
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSAdmin();

$db    = getCMSDB();
$flash = cmsGetFlash();

$roles = $db->query(
    "SELECT r.id, r.name,
            (SELECT COUNT(*) FROM users WHERE role_id = r.id) AS user_count
     FROM roles r ORDER BY r.id"
)->fetchAll();

$pageTitle = 'Roles';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Roles</h1>
</div>

<div class="card">
    <p class="hint">Roles are seeded at install time. To change a user's role, edit the user.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Users</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= (int) $role['id'] ?></td>
                    <td><span class="badge badge--<?= e($role['name']) ?>"><?= e($role['name']) ?></span></td>
                    <td><?= (int) $role['user_count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
