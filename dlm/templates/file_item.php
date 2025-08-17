<?php
/**
 * templates/file_item.php
 *
 * Renders a single file card for the public file listing.
 *
 * Expects the following variable in scope:
 *   $file  (array)  – A dm_downloads row with all columns.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */
?>
<article class="file-card" data-type="<?= e(fileTypeClass($file['mime_type'])) ?>">
    <div class="file-card__icon" aria-hidden="true">
        <?= fileTypeIcon($file['mime_type']) ?>
    </div>

    <div class="file-card__body">
        <div class="file-card__meta">
            <?php if ($file['category'] !== ''): ?>
                <a href="<?= SITE_URL ?>?category=<?= urlencode($file['category']) ?>"
                   class="file-card__category"><?= e($file['category']) ?></a>
            <?php endif; ?>
            <span class="file-card__date"><?= e(formatDate($file['created_at'])) ?></span>
        </div>

        <h2 class="file-card__title">
            <a href="<?= SITE_URL ?>/download.php?id=<?= (int) $file['id'] ?>">
                <?= e($file['title']) ?>
            </a>
        </h2>

        <?php if ($file['description'] !== ''): ?>
            <p class="file-card__desc"><?= e($file['description']) ?></p>
        <?php endif; ?>

        <div class="file-card__footer">
            <span class="file-card__size">&#128190; <?= e(formatFileSize((int) $file['file_size'])) ?></span>
            <span class="file-card__downloads">&#8595; <?= number_format((int) $file['download_count']) ?> downloads</span>
            <a href="<?= SITE_URL ?>/download.php?id=<?= (int) $file['id'] ?>"
               class="btn btn--primary btn--sm">Download</a>
        </div>
    </div>
</article>
