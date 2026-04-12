<?php
/**
 * templates/thread_row.php
 *
 * Renders a single row in a thread listing.
 * Expects $thread (array) with keys from getThreadsByCategory(), including:
 *   id, title, author_name, user_id, created_at, post_count, last_post_at,
 *   last_poster, is_sticky, is_locked, view_count
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */
?>
<article class="thread-row<?= $thread['is_sticky'] ? ' thread-row--sticky' : '' ?>">
    <div class="thread-row__flags">
        <?php if ($thread['is_sticky']): ?>
            <span class="badge badge--sticky" title="Sticky thread">Sticky</span>
        <?php endif; ?>
        <?php if ($thread['is_locked']): ?>
            <span class="badge badge--locked" title="Locked thread">Locked</span>
        <?php endif; ?>
    </div>

    <div class="thread-row__main">
        <h3 class="thread-row__title">
            <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $thread['id'] ?>">
                <?= e($thread['title']) ?>
            </a>
        </h3>
        <div class="thread-row__meta">
            By
            <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $thread['user_id'] ?>">
                <?= e($thread['author_name']) ?>
            </a>
            on <?= e(formatDate($thread['created_at'])) ?>
        </div>
    </div>

    <div class="thread-row__stats">
        <span class="thread-row__stat">
            <strong><?= number_format((int) $thread['post_count']) ?></strong>
            <small>posts</small>
        </span>
        <span class="thread-row__stat">
            <strong><?= number_format((int) $thread['view_count']) ?></strong>
            <small>views</small>
        </span>
    </div>

    <div class="thread-row__last">
        <?php if (!empty($thread['last_post_at'])): ?>
            <small class="thread-row__last-time"><?= e(formatDate($thread['last_post_at'])) ?></small>
            <?php if (!empty($thread['last_poster'])): ?>
                <small>by <?= e($thread['last_poster']) ?></small>
            <?php endif; ?>
        <?php else: ?>
            <small class="thread-row__last-time text-muted">No replies yet</small>
        <?php endif; ?>
    </div>
</article>
