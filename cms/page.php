<?php
/**
 * cms/page.php
 *
 * Displays a single published static CMS page by slug.
 * Route: /cms/page/{slug}  (or accessed directly as page.php?slug=...)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$slug = trim($_GET['slug'] ?? '');
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if ($slug === '') {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$stmt = getCMSDB()->prepare(
    "SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$pg = $stmt->fetch();

if (!$pg) {
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    require_once CMS_ROOT . '/templates/header.php';
    echo '<div class="card"><h1>404 – Page Not Found</h1></div>';
    require_once CMS_ROOT . '/templates/footer.php';
    exit;
}

$pageTitle = $pg['title'];
require_once CMS_ROOT . '/templates/header.php';
?>

<article class="card">
    <h1><?= e($pg['title']) ?></h1>
    <p style="font-size:.8rem;color:var(--color-muted);margin-bottom:1.5rem">
        Published <?= e(cmsFormatDate($pg['created_at'])) ?>
    </p>
    <div class="page-content">
        <?= $pg['content'] /* Stored as HTML; output unescaped intentionally */ ?>
    </div>
</article>

<?php require_once CMS_ROOT . '/templates/footer.php'; ?>
