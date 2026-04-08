<?php
/**
 * post.php
 *
 * Single post view page. Resolves a post either by:
 *   - ?slug=my-post-slug   (preferred – clean URL via .htaccess rewrite)
 *   - ?id=123              (fallback numeric ID)
 *
 * Renders the full post content, author, date, category, tags, and
 * a social sharing toolbar.
 *
 * Sends a 404 header and terminates if the post is not found or is a draft.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------------------------
// Resolve post
// ---------------------------------------------------------------------------

$post = null;

if (!empty($_GET['slug'])) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'])));
    $post = getPostBySlug($slug);
} elseif (!empty($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $row  = getPostById($id);
    // Only serve published posts on the public post page
    if ($row && $row['status'] === 'published') {
        $post = $row;
    }
}

if ($post === null) {
    http_response_code(404);
    $pageTitle = '404 – Post Not Found';
    $metaDesc  = 'The requested post could not be found.';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="empty-state"><h2>404 – Post Not Found</h2>';
    echo '<p>The post you are looking for does not exist or has been removed.</p>';
    echo '<a href="' . SITE_URL . '" class="btn btn--primary" style="margin-top:1rem;">&larr; Back to Home</a></div>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Meta for this page
// ---------------------------------------------------------------------------
$pageTitle = $post['title'];
$metaDesc  = !empty($post['excerpt']) ? $post['excerpt'] : makeExcerpt($post['content'], 160);
$tags      = getTagsForPost((int) $post['id']);
$postUrl   = SITE_URL . '/post/' . e($post['slug']);

require_once __DIR__ . '/templates/header.php';
?>

<article class="post-single" itemscope itemtype="https://schema.org/BlogPosting">

    <header class="post-single__header">
        <?php if (!empty($post['category_name'])): ?>
            <a href="<?= SITE_URL ?>/category/<?= e($post['category_slug']) ?>" class="post-single__category">
                <?= e($post['category_name']) ?>
            </a>
        <?php endif; ?>

        <h1 class="post-single__title" itemprop="headline"><?= e($post['title']) ?></h1>

        <div class="post-single__meta">
            <span>By <strong itemprop="author"><?= e($post['author_name'] ?? DEFAULT_AUTHOR) ?></strong></span>
            <time datetime="<?= e($post['created_at']) ?>" itemprop="datePublished">
                <?= formatDate($post['created_at']) ?>
            </time>
            <?php if ($post['updated_at'] !== $post['created_at']): ?>
                <span class="post-single__updated">
                    Updated: <time datetime="<?= e($post['updated_at']) ?>"><?= formatDate($post['updated_at']) ?></time>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($post['featured_image'])): ?>
        <img
            src="<?= SITE_URL ?>/assets/images/uploads/<?= e($post['featured_image']) ?>"
            alt="<?= e($post['title']) ?>"
            class="post-single__featured-image"
            itemprop="image"
        >
    <?php endif; ?>

    <div class="post-single__content" itemprop="articleBody">
        <?= $post['content'] /* Content is stored as sanitised HTML from the admin editor */ ?>
    </div>

    <?php if (!empty($tags)): ?>
        <div class="post-single__tags">
            <span>Tags:</span>
            <?php foreach ($tags as $tag): ?>
                <a href="<?= SITE_URL ?>/tag/<?= e($tag['slug']) ?>" class="tag-pill">
                    <?= e($tag['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="post-single__share">
        <span>Share:</span>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($postUrl) ?>&text=<?= urlencode($post['title']) ?>"
           class="btn btn--sm btn--outline" target="_blank" rel="noopener noreferrer">Twitter</a>
        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($postUrl) ?>&title=<?= urlencode($post['title']) ?>"
           class="btn btn--sm btn--outline" target="_blank" rel="noopener noreferrer">LinkedIn</a>
        <a href="https://news.ycombinator.com/submitlink?u=<?= urlencode($postUrl) ?>&t=<?= urlencode($post['title']) ?>"
           class="btn btn--sm btn--outline" target="_blank" rel="noopener noreferrer">HN</a>
    </div>

</article>

<div style="margin-top:2.5rem;">
    <a href="<?= SITE_URL ?>" class="btn btn--outline">&larr; All Posts</a>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
