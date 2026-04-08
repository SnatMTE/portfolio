<?php
/**
 * admin/create_post.php
 *
 * Form for creating a new blog post.
 *
 * On GET:  Renders the creation form with category/tag selectors.
 * On POST: Validates input, inserts the post into the database, syncs tags,
 *          handles an optional featured image upload, then redirects to
 *          the posts list with a flash message.
 *
 * Validation rules:
 *   - Title:   Required, max 255 characters.
 *   - Content: Required.
 *   - Slug:    Auto-generated from title if omitted; must be unique.
 *   - Status:  Must be 'published' or 'draft'.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

$categories = getAllCategories();
$allTags    = getAllTags();
$errors     = [];
$formData   = [
    'title'       => '',
    'slug'        => '',
    'content'     => '',
    'excerpt'     => '',
    'category_id' => '',
    'status'      => 'draft',
    'tag_ids'     => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        // Collect and sanitise input
        $formData['title']       = trim($_POST['title'] ?? '');
        $formData['slug']        = slugify(trim($_POST['slug'] ?? $formData['title']));
        $formData['content']     = trim($_POST['content'] ?? '');
        $formData['excerpt']     = trim($_POST['excerpt'] ?? '');
        $formData['category_id'] = (int) ($_POST['category_id'] ?? 0) ?: null;
        $formData['status']      = in_array($_POST['status'] ?? '', ['published', 'draft'], true)
                                        ? $_POST['status']
                                        : 'draft';
        $formData['tag_ids']     = array_map('intval', (array) ($_POST['tag_ids'] ?? []));

        // Validate
        if ($formData['title'] === '') {
            $errors[] = 'Title is required.';
        } elseif (mb_strlen($formData['title']) > 255) {
            $errors[] = 'Title must not exceed 255 characters.';
        }

        if ($formData['content'] === '') {
            $errors[] = 'Content is required.';
        }

        // Ensure slug uniqueness
        if ($formData['slug'] !== '') {
            $slugCheck = getDB()->prepare("SELECT id FROM posts WHERE slug = :slug");
            $slugCheck->execute([':slug' => $formData['slug']]);
            if ($slugCheck->fetch()) {
                $errors[] = 'The slug "' . e($formData['slug']) . '" is already in use. Please choose a different one.';
            }
        }
    }

    if (empty($errors)) {
        // Auto-generate excerpt if not provided
        if ($formData['excerpt'] === '') {
            $formData['excerpt'] = makeExcerpt($formData['content']);
        }

        // Handle featured image upload
        $featuredImage = null;
        if (!empty($_FILES['featured_image']['name'])) {
            $featuredImage = handleImageUpload($_FILES['featured_image']);
            if ($featuredImage === null) {
                $errors[] = 'Image upload failed. Allowed types: JPEG, PNG, GIF, WebP (max 5 MB).';
            }
        }
    }

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("
            INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, category_id, status)
            VALUES (:title, :slug, :content, :excerpt, :featured_image, :author_id, :category_id, :status)
        ");
        $stmt->execute([
            ':title'          => $formData['title'],
            ':slug'           => $formData['slug'],
            ':content'        => $formData['content'],
            ':excerpt'        => $formData['excerpt'],
            ':featured_image' => $featuredImage,
            ':author_id'      => (int) $_SESSION['admin_id'],
            ':category_id'    => $formData['category_id'],
            ':status'         => $formData['status'],
        ]);

        $postId = (int) $db->lastInsertId();

        // Sync tags via post_tags join table
        syncPostTags($postId, $formData['tag_ids']);

        flashMessage('Post "' . $formData['title'] . '" created successfully.', 'success');
        redirect(SITE_URL . '/admin/posts.php');
    }
}

$currentAdminPage = 'create_post';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Post – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Quill editor (no API key required) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
</head>
<body>
<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>New Post</h1>
            <a href="<?= SITE_URL ?>/admin/posts.php" class="btn btn--outline btn--sm">← All Posts</a>
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
            <form method="post" action="" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label for="post-title">Title <span style="color:#ef4444;">*</span></label>
                    <input id="post-title" type="text" name="title" class="form-control"
                           value="<?= e($formData['title']) ?>" maxlength="255" required>
                </div>

                <div class="form-group">
                    <label for="post-slug">Slug <span style="color:var(--clr-text-muted);font-weight:400;">(auto-generated if left blank)</span></label>
                    <input id="post-slug" type="text" name="slug" class="form-control"
                           value="<?= e($formData['slug']) ?>" placeholder="my-post-slug" maxlength="255">
                </div>

                <div class="form-group">
                    <label for="content-editor">Content <span style="color:#ef4444;">*</span></label>
                        <div id="quill-editor" style="background:#fff;border:1px solid var(--clr-border);border-radius:var(--radius-md);min-height:320px;"></div>
                        <textarea id="content-editor" name="content" class="form-control" style="display:none;" rows="14"><?= e($formData['content']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="post-excerpt">Excerpt <span style="color:var(--clr-text-muted);font-weight:400;">(auto-generated if left blank)</span></label>
                    <textarea id="post-excerpt" name="excerpt" class="form-control" rows="3"
                              maxlength="500"><?= e($formData['excerpt']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="post-category">Category</label>
                        <select id="post-category" name="category_id" class="form-control">
                            <option value="">— None —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= (int)($formData['category_id']) === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="post-status">Status</label>
                        <select id="post-status" name="status" class="form-control">
                            <option value="draft"     <?= $formData['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $formData['status'] === 'published' ? 'selected' : '' ?>>Published</option>
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
                                        <?= in_array((int)$tag['id'], $formData['tag_ids'], true) ? 'checked' : '' ?>>
                                    <?= e($tag['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="featured-image">Featured Image</label>
                    <input id="featured-image" type="file" name="featured_image" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color:var(--clr-text-muted);">JPEG, PNG, GIF or WebP · max 5 MB</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Create Post</button>
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

    // Populate initial content (if any)
    var initialContent = <?= json_encode($formData['content']) ?>;
    if (initialContent) {
        quill.clipboard.dangerouslyPasteHTML(initialContent);
    }

    // On submit, copy HTML into the hidden textarea so the server receives it
    var form = document.querySelector('form');
    form.addEventListener('submit', function () {
        document.getElementById('content-editor').value = quill.root.innerHTML;
    });
});
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
