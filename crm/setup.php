<?php
/**
 * crm/setup.php
 *
 * One-time CRM setup script.
 * Initialises the database schema and optionally seeds demo data.
 * Delete or protect this file after running.
 */

define('CRM_SETUP', true);

require_once __DIR__ . '/config.php';

$pdo = getCRMDB();

// Schema is always initialised by getCRMDB(), so we just confirm here.
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'crm_%'")->fetchAll(PDO::FETCH_COLUMN);

$seeded = false;
if (file_exists(__DIR__ . '/DEMO') && function_exists('seedDemoCRM')) {
    seedDemoCRM($pdo);
    $seeded = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRM Setup</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 4rem auto; padding: 0 1rem; color: #111; }
        h1   { color: #f97316; }
        .ok  { color: #16a34a; }
        .info { color: #2563eb; }
        ul   { line-height: 2; }
    </style>
</head>
<body>
    <h1>&#x2705; CRM Setup Complete</h1>
    <p>The following tables are ready:</p>
    <ul>
        <?php foreach ($tables as $t): ?>
            <li class="ok">&#10003; <?= htmlspecialchars($t, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php if ($seeded): ?>
        <p class="info">&#128310; Demo data seeded from <code>db/demo_seed.php</code>.</p>
    <?php endif; ?>
    <p><strong>Important:</strong> Delete or restrict access to <code>setup.php</code> now.</p>
    <p><a href="<?= SITE_URL ?>/crm/">&#8594; Go to CRM</a></p>
</body>
</html>
