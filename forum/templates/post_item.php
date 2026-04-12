<?php
/**
 * templates/post_item.php
 *
 * Renders a single post within a thread view.
 * Expects:
 *   $post          (array)  - Post row from getPostsByThread(), including author_name, author_bio, author_joined.
 *   $threadOwnerId (int)    - User ID of the thread's original author.
 *   $postNumber    (int)    - 1-based position of this post in the full thread.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

$isOp          = (int) $post['user_id'] === (int) $threadOwnerId;
$isOwn         = isLoggedIn() && (int) currentUser()['id'] === (int) $post['user_id'];
$canDelete     = $isOwn || isAdmin();
$postInitial   = strtoupper(mb_substr($post['author_name'], 0, 1));
?>
<article class="post-item<?= $isOp ? ' post-item--op' : '' ?>" id="post-<?= (int) $post['id'] ?>">

    <aside class="post-item__sidebar">
          <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $post['user_id'] ?>"
              class="post-item__avatar" aria-label="<?= e($post['author_name']) ?>'s profile">
                <img src="<?= e(gravatar_url($post['author_email'] ?? '', 52)) ?>"
                      alt="<?= e($post['author_name']) ?>'s avatar" width="52" height="52">
          </a>
        <div class="post-item__author-info">
            <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $post['user_id'] ?>"
               class="post-item__author-name"><?= e($post['author_name']) ?></a>
            <?php if ($isOp): ?>
                <span class="badge badge--op">OP</span>
            <?php endif; ?>
            <?php if (!empty($post['author_bio'])): ?>
                <p class="post-item__author-bio"><?= e(truncate($post['author_bio'], 80)) ?></p>
            <?php endif; ?>
            <span class="post-item__author-joined">
                Joined <?= e(formatDate($post['author_joined'])) ?>
            </span>
        </div>
    </aside>

    <div class="post-item__body">
        <header class="post-item__header">
            <a href="#post-<?= (int) $post['id'] ?>" class="post-item__num">#<?= (int) $postNumber ?></a>
            <time class="post-item__time" datetime="<?= e($post['created_at']) ?>">
                <?= e(formatDateTime($post['created_at'])) ?>
            </time>
            <?php if ($canDelete): ?>
                <div class="post-item__actions">
                    <form method="post" action="<?= SITE_URL ?>/thread.php?id=<?= (int) $post['thread_id'] ?>"
                          onsubmit="return confirm('Delete this post?')">
                        <input type="hidden" name="action"      value="delete_post">
                        <input type="hidden" name="post_id"     value="<?= (int) $post['id'] ?>">
                        <input type="hidden" name="csrf_token"  value="<?= e(csrfToken()) ?>">
                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                    </form>
                </div>
            <?php endif; ?>
        </header>

        <div class="post-item__content">
            <?= nl2br(e($post['content'])) ?>
        </div>

        <?php if ($post['updated_at'] !== $post['created_at']): ?>
            <p class="post-item__edited">
                <small>Edited <?= e(formatDateTime($post['updated_at'])) ?></small>
            </p>
        <?php endif; ?>
    </div>

</article>
