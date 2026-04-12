<?php
/**
 * admin/edit_post.php
 *
 * Form for editing an existing blog post.
 *
 * On GET:  Loads the existing post data (identified by ?id=NNN) and
 *          pre-populates the form fields.
 * On POST: Validates input, updates the post row, re-syncs tags, and
 *          handles a replacement featured image if one is uploaded.
 *
 * Redirects to posts.php with a flash message after a successful update.
 * Returns a 404 notice if the requested post ID does not exist.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Load post
// ---------------------------------------------------------------------------
$postId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($postId === 0) {
    redirect(SITE_URL . '/admin/posts.php');
}

$post = getPostById($postId);
if ($post === null) {
    http_response_code(404);
    flashMessage('Post not found.', 'error');
    redirect(SITE_URL . '/admin/posts.php');
}

$categories     = getAllCategories();
$allTags        = getAllTags();
$currentTagIds  = array_column(getTagsForPost($postId), 'id');
$errors         = [];

// ---------------------------------------------------------------------------
// Handle POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $title      = trim($_POST['title'] ?? '');
        $slug       = slugify(trim($_POST['slug'] ?? $title));
        $content    = trim($_POST['content'] ?? '');
        $excerpt    = trim($_POST['excerpt'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
        $status     = in_array($_POST['status'] ?? '', ['published', 'draft'], true)
                          ? $_POST['status'] : 'draft';
        $tagIds     = array_map('intval', (array) ($_POST['tag_ids'] ?? []));

        // Validate
        if ($title === '') {
            $errors[] = 'Title is required.';
        } elseif (mb_strlen($title) > 255) {
            $errors[] = 'Title must not exceed 255 characters.';
        }

        if ($content === '') {
            $errors[] = 'Content is required.';
        }

        if ($slug !== '') {
            $slugCheck = getDB()->prepare("SELECT id FROM posts WHERE slug = :slug AND id != :id");
            $slugCheck->execute([':slug' => $slug, ':id' => $postId]);
            if ($slugCheck->fetch()) {
                $errors[] = 'The slug "' . e($slug) . '" is already taken by another post.';
            }
        }
    }

    if (empty($errors)) {
        if ($excerpt === '') {
            $excerpt = makeExcerpt($content);
        }

        // Handle optional new featured image
        $featuredImage = $post['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            $uploaded = handleImageUpload($_FILES['featured_image']);
            if ($uploaded === null) {
                $errors[] = 'Image upload failed. Allowed types: JPEG, PNG, GIF, WebP (max 5 MB).';
            } else {
                $featuredImage = $uploaded;
            }
        }
    }

    if (empty($errors)) {
        $stmt = getDB()->prepare("
            UPDATE posts
            SET title          = :title,
                slug           = :slug,
                content        = :content,
                excerpt        = :excerpt,
                featured_image = :featured_image,
                category_id    = :category_id,
                status         = :status,
                updated_at     = datetime('now')
            WHERE id = :id
        ");
        $stmt->execute([
            ':title'          => $title,
            ':slug'           => $slug,
            ':content'        => $content,
            ':excerpt'        => $excerpt,
            ':featured_image' => $featuredImage,
            ':category_id'    => $categoryId,
            ':status'         => $status,
            ':id'             => $postId,
        ]);

        syncPostTags($postId, $tagIds);

        flashMessage('Post "' . $title . '" updated successfully.', 'success');
        redirect(SITE_URL . '/admin/posts.php');
    }

    // Re-populate form data from POST on validation failure
    $post = array_merge($post, [
        'title'       => $title       ?? $post['title'],
        'slug'        => $slug        ?? $post['slug'],
        'content'     => $content     ?? $post['content'],
        'excerpt'     => $excerpt     ?? $post['excerpt'],
        'category_id' => $categoryId  ?? $post['category_id'],
        'status'      => $status      ?? $post['status'],
    ]);
    $currentTagIds = $tagIds ?? $currentTagIds;
}

$currentAdminPage = 'posts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
</head>
<body>
<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Edit Post</h1>
            <div style="display:flex;gap:.5rem;">
                <a href="<?= SITE_URL ?>/post/<?= e($post['slug']) ?>" class="btn btn--outline btn--sm" target="_blank">View Post ↗</a>
                <a href="<?= SITE_URL ?>/admin/posts.php" class="btn btn--outline btn--sm">← All Posts</a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error" role="alert">
                <strong>Please fix the following:</strong>
                <ul style="margin-top:.4rem;padding-left:1.25rem;list-style:disc;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="admin-form-card">
            <form method="post" action="?id=<?= $postId ?>" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id"         value="<?= $postId ?>">

                <div class="form-group">
                    <label for="post-title">Title <span style="color:#ef4444;">*</span></label>
                    <input id="post-title" type="text" name="title" class="form-control"
                           value="<?= e($post['title']) ?>" maxlength="255" required>
                </div>

                <div class="form-group">
                    <label for="post-slug">Slug</label>
                    <input id="post-slug" type="text" name="slug" class="form-control"
                           value="<?= e($post['slug']) ?>" maxlength="255">
                </div>

                <div class="form-group">
                    <label for="content-editor">Content <span style="color:#ef4444;">*</span></label>
                        <div id="quill-editor" style="background:#fff;border:1px solid var(--clr-border);border-radius:var(--radius-md);min-height:320px;"></div>
                        <textarea id="content-editor" name="content" class="form-control" style="display:none;" rows="14"><?= e($post['content']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="post-excerpt">Excerpt</label>
                    <textarea id="post-excerpt" name="excerpt" class="form-control" rows="3"
                              maxlength="500"><?= e($post['excerpt']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="post-category">Category</label>
                        <select id="post-category" name="category_id" class="form-control">
                            <option value="">— None —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= (int)($post['category_id']) === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="post-status">Status</label>
                        <select id="post-status" name="status" class="form-control">
                            <option value="draft"     <?= $post['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($allTags)): ?>
                    <div class="form-group">
                        <label>Tags</label>
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.35rem;">
                            <?php foreach ($allTags as $tag): ?>
                                <label style="display:flex;align-items:center;gap:.3rem;font-weight:400;cursor:pointer;">
                                    <input type="checkbox" name="tag_ids[]" value="<?= (int)$tag['id'] ?>"
                                        <?= in_array((int)$tag['id'], array_map('intval', $currentTagIds), true) ? 'checked' : '' ?>>
                                    <?= e($tag['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="featured-image">
                        Featured Image
                        <?php if (!empty($post['featured_image'])): ?>
                            <span style="font-weight:400;color:var(--clr-text-muted);">(replace existing)</span>
                        <?php endif; ?>
                    </label>
                    <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= SITE_URL ?>/assets/images/uploads/<?= e($post['featured_image']) ?>"
                             alt="Current featured image"
                             style="margin-bottom:.5rem;max-height:160px;border-radius:var(--radius-md);">
                    <?php endif; ?>
                    <input id="featured-image" type="file" name="featured_image" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color:var(--clr-text-muted);">JPEG, PNG, GIF or WebP · max 5 MB</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Save Changes</button>
                    <a href="<?= SITE_URL ?>/admin/posts.php" class="btn btn--outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'blockquote'],
                [{ 'header': 2 }, { 'header': 3 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'image', 'code-block'],
                ['clean']
            ]
        }
    });

    // Populate with existing content
    var initial = <?= json_encode($post['content']) ?>;
    if (initial) {
        quill.clipboard.dangerouslyPasteHTML(initial);
    }

    // Copy HTML into hidden textarea before submit
    var form = document.querySelector('form');
    form.addEventListener('submit', function () {
        document.getElementById('content-editor').value = quill.root.innerHTML;
    });
});
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
