<?php
/**
 * templates/upload_form.php
 *
 * Reusable upload/edit form fragment.
 * Used by both upload.php and edit.php.
 *
 * Expects:
 *   $file        (array|null)  – Existing dm_downloads row when editing, null for new.
 *   $formAction  (string)      – Form action URL.
 *   $errors      (array)       – Array of validation error strings.
 *   $categories  (array)       – Existing category names for the datalist.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

$isEdit     = $file !== null;
$title      = $file['title']       ?? ($_POST['title']       ?? '');
$desc       = $file['description'] ?? ($_POST['description'] ?? '');
$category   = $file['category']    ?? ($_POST['category']    ?? '');
$visibility = $file['visibility']  ?? ($_POST['visibility']  ?? 'public');
?>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
        <div class="alert alert--error" role="alert"><?= e($err) ?></div>
    <?php endforeach; ?>
<?php endif; ?>

<form method="post"
      action="<?= e($formAction) ?>"
      enctype="multipart/form-data"
      novalidate
      id="upload-form">

    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label for="title">Title <span class="required">*</span></label>
        <input id="title" type="text" name="title" class="form-control"
               maxlength="200" required value="<?= e($title) ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control"
                  maxlength="1000" rows="4"><?= e($desc) ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="category">Category</label>
            <input id="category" type="text" name="category" class="form-control"
                   maxlength="80" list="category-list"
                   placeholder="e.g. Documentation"
                   value="<?= e($category) ?>">
            <datalist id="category-list">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="form-group">
            <label for="visibility">Visibility <span class="required">*</span></label>
            <select id="visibility" name="visibility" class="form-control">
                <option value="public"  <?= $visibility === 'public'  ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Private</option>
            </select>
        </div>
    </div>

    <?php if (!$isEdit): ?>
        <!-- File input with drag-and-drop zone -->
        <div class="form-group">
            <label for="file">File <span class="required">*</span></label>
            <div class="upload-zone" id="upload-zone">
                <div class="upload-zone__inner">
                    <span class="upload-zone__icon" aria-hidden="true">&#128228;</span>
                    <p class="upload-zone__text">Drag &amp; drop a file here, or <label for="file" class="upload-zone__browse">browse</label></p>
                    <p class="upload-zone__hint">Max <?= e(formatFileSize(DM_MAX_UPLOAD)) ?></p>
                    <p class="upload-zone__filename" id="upload-filename" hidden></p>
                </div>
                <input id="file" type="file" name="file" required
                       aria-describedby="upload-filename"
                       style="position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;">
            </div>
        </div>
    <?php else: ?>
        <!-- Replace file (optional when editing) -->
        <div class="form-group">
            <label for="file">Replace File <span style="font-weight:400;color:var(--clr-text-muted)">(optional — leave blank to keep current)</span></label>
            <div class="upload-zone" id="upload-zone">
                <div class="upload-zone__inner">
                    <span class="upload-zone__icon" aria-hidden="true">&#128228;</span>
                    <p class="upload-zone__text">Drag &amp; drop a replacement file, or <label for="file" class="upload-zone__browse">browse</label></p>
                    <p class="upload-zone__hint">Current: <strong><?= e($file['original_name']) ?></strong>
                        (<?= e(formatFileSize((int) $file['file_size'])) ?>)</p>
                    <p class="upload-zone__filename" id="upload-filename" hidden></p>
                </div>
                <input id="file" type="file" name="file"
                       aria-describedby="upload-filename"
                       style="position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;">
            </div>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            <?= $isEdit ? '&#10003; Save Changes' : '&#8679; Upload File' ?>
        </button>
        <a href="<?= SITE_URL ?>/admin/files.php" class="btn btn--outline">Cancel</a>
    </div>
</form>
