<?php
/**
 * crm/db/demo_seed.php
 *
 * Inserts sample companies, customers, leads, tasks, and notes.
 * Called by getCRMDB() when a DEMO file is present in the module root.
 *
 * @param PDO $pdo Active database connection.
 * @return void
 */
function seedDemoCRM(PDO $pdo): void
{
    // Don't re-seed if data already exists.
    $count = (int) $pdo->query("SELECT COUNT(*) FROM crm_companies")->fetchColumn();
    if ($count > 0) {
        return;
    }

    /* ── Companies ─────────────────────────────────────────── */
    $companies = [
        ['Acme Corp',       'Technology',   'https://example.com', 'London',    'GB'],
        ['Blue Horizon Ltd','Finance',       'https://example.org', 'Manchester','GB'],
        ['Sunrise Media',   'Marketing',    'https://example.net', 'Edinburgh', 'GB'],
    ];

    $addCo = $pdo->prepare(
        "INSERT INTO crm_companies (name, industry, website, city, country) VALUES (?,?,?,?,?)"
    );
    foreach ($companies as $c) { $addCo->execute($c); }

    $coIds = $pdo->query("SELECT id FROM crm_companies LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);

    /* ── Customers ─────────────────────────────────────────── */
    $customers = [
        ['Alice',   'Thornton',  'alice@example.com',  '07700900001', 'Product Manager',  $coIds[0], 'active'],
        ['Bob',     'Hargreaves','bob@example.com',    '07700900002', 'CTO',               $coIds[0], 'active'],
        ['Carol',   'Singh',     'carol@example.com',  '07700900003', 'Finance Director',  $coIds[1], 'active'],
        ['David',   'Okafor',    'david@example.com',  '07700900004', 'Marketing Manager', $coIds[2], 'prospect'],
        ['Eleanor', 'Walsh',     'eleanor@example.com','07700900005', 'CEO',               null,      'inactive'],
    ];

    $addCust = $pdo->prepare(
        "INSERT INTO crm_customers
            (first_name, last_name, email, phone, job_title, company_id, status)
         VALUES (?,?,?,?,?,?,?)"
    );
    foreach ($customers as $c) { $addCust->execute($c); }

    $custIds = $pdo->query("SELECT id FROM crm_customers LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);

    /* ── Leads ─────────────────────────────────────────────── */
    $leads = [
        ['Enterprise SaaS Licence', $custIds[0], $coIds[0], 'new',          'high',   18000.00, date('Y-m-d', strtotime('+30 days'))],
        ['Annual Retainer',         $custIds[2], $coIds[1], 'proposal',     'medium', 9600.00,  date('Y-m-d', strtotime('+60 days'))],
        ['Brand Refresh Package',   $custIds[3], $coIds[2], 'qualified',    'medium', 4500.00,  date('Y-m-d', strtotime('+45 days'))],
        ['Staff Training Day',      $custIds[1], $coIds[0], 'won',          'low',    1200.00,  date('Y-m-d', strtotime('-5 days'))],
        ['Website Redevelopment',   $custIds[4], null,      'contacted',    'high',   12000.00, date('Y-m-d', strtotime('+90 days'))],
    ];

    $addLead = $pdo->prepare(
        "INSERT INTO crm_leads (title, customer_id, company_id, stage, priority, value, currency, close_date)
         VALUES (?,?,?,?,?,?,?,?)"
    );
    foreach ($leads as $l) {
        $addLead->execute([$l[0], $l[1], $l[2], $l[3], $l[4], $l[5], 'GBP', $l[6]]);
    }

    $leadIds = $pdo->query("SELECT id FROM crm_leads LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);

    /* ── Tasks ─────────────────────────────────────────────── */
    $tasks = [
        ['Send proposal to Alice',     'lead',     $leadIds[0], 'pending',     'high',   date('Y-m-d H:i:s', strtotime('+2 days'))],
        ['Follow up with Carol',       'customer', $custIds[2], 'in_progress', 'medium', date('Y-m-d H:i:s', strtotime('+5 days'))],
        ['Review brand brief',         'lead',     $leadIds[2], 'pending',     'medium', date('Y-m-d H:i:s', strtotime('+7 days'))],
        ['Invoice Bob for training',   'lead',     $leadIds[3], 'completed',   'low',    date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['Kick-off call with Eleanor', 'customer', $custIds[4], 'pending',     'urgent', date('Y-m-d H:i:s', strtotime('+1 day'))],
    ];

    $addTask = $pdo->prepare(
        "INSERT INTO crm_tasks (title, related_type, related_id, status, priority, due_date)
         VALUES (?,?,?,?,?,?)"
    );
    foreach ($tasks as $t) { $addTask->execute($t); }

    /* ── Notes ─────────────────────────────────────────────── */
    $notes = [
        ['customer', $custIds[0], 'Alice is keen to move fast — wants a demo by end of the month.'],
        ['lead',     $leadIds[1], 'Carol asked for a payment schedule. Draft one and share before proposal.'],
        ['lead',     $leadIds[4], 'Eleanor was referred by a mutual contact. Warm lead but timeline is flexible.'],
    ];

    $addNote = $pdo->prepare(
        "INSERT INTO crm_notes (related_type, related_id, content) VALUES (?,?,?)"
    );
    foreach ($notes as $n) { $addNote->execute($n); }
}
