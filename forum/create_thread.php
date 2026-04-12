<?php
/**
 * create_thread.php
 *
 * Form for creating a new thread in a category.
 * URL: create_thread.php?category_id={id}
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';
requireLogin();

$categoryId = (int) ($_GET['category_id'] ?? $_POST['category_id'] ?? 0);
$category   = $categoryId > 0 ? getCategoryById($categoryId) : null;

// If no specific category, let the user pick
$allCategories = getCategories();

$errors = [];
$formTitle   = '';
$formContent = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $formTitle   = trim($_POST['title']   ?? '');
    $formContent = trim($_POST['content'] ?? '');
    $category    = $categoryId > 0 ? getCategoryById($categoryId) : null;

    if ($category === null) {
        $errors[] = 'Please select a valid category.';
    }
    if ($formTitle === '') {
        $errors[] = 'Thread title cannot be empty.';
    } elseif (mb_strlen($formTitle) > 200) {
        $errors[] = 'Thread title is too long (maximum 200 characters).';
    }
    if ($formContent === '') {
        $errors[] = 'Post content cannot be empty.';
    } elseif (mb_strlen($formContent) > 10000) {
        $errors[] = 'Post content is too long (maximum 10,000 characters).';
    }

    if (empty($errors)) {
        $threadId = createThread($categoryId, (int) currentUser()['id'], $formTitle, $formContent);
        flashMessage('Your thread has been created.', 'success');
        redirect(SITE_URL . '/thread.php?id=' . $threadId);
    }
}

$pageTitle = 'New Thread' . ($category ? ' in ' . $category['name'] : '');
require_once __DIR__ . '/templates/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
        <li><a href="<?= SITE_URL ?>">Home</a></li>
        <?php if ($category): ?>
            <li>
                <a href="<?= SITE_URL ?>/category.php?slug=<?= e($category['slug']) ?>">
                    <?= e($category['name']) ?>
                </a>
            </li>
        <?php endif; ?>
        <li aria-current="page">New Thread</li>
    </ol>
</nav>

<h1 class="page-heading">Start a New Thread</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert--error" role="alert">
        <ul class="error-list">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-form-card">
    <form method="post" action="<?= SITE_URL ?>/create_thread.php" class="thread-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-control" required>
                <option value="">Select a category...</option>
                <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"
                        <?= (int) $cat['id'] === $categoryId ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Thread Title</label>
            <input type="text" name="title" id="title" class="form-control"
                   value="<?= e($formTitle) ?>" placeholder="Enter a descriptive title..."
                   required maxlength="200">
        </div>

        <div class="form-group">
            <label for="content">Your Post</label>
            <textarea name="content" id="content" class="form-control reply-textarea"
                      placeholder="Write your post..." rows="10" required
                      maxlength="10000"><?= e($formContent) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Post Thread</button>
            <?php if ($category): ?>
                <a href="<?= SITE_URL ?>/category.php?slug=<?= e($category['slug']) ?>"
                   class="btn btn--outline">Cancel</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/" class="btn btn--outline">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
