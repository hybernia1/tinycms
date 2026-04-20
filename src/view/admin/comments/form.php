<?php
if (!defined('BASE_DIR')) {
    exit;
}

$item = is_array($item ?? null) ? $item : [];
$errors = is_array($errors ?? null) ? $errors : [];
$publishedIn = is_array($publishedIn ?? null) ? $publishedIn : [];
$id = (int)($item['id'] ?? 0);
$contentName = trim((string)($publishedIn['content_name'] ?? ''));
$adminEditUrl = trim((string)($publishedIn['admin_edit_url'] ?? ''));
$frontUrl = trim((string)($publishedIn['front_url'] ?? ''));
$body = (string)($item['body'] ?? '');
?>

<form
    id="comments-form"
    method="post"
    action="<?= $e($url('admin/api/v1/comments/' . $id . '/edit')) ?>"
    data-api-submit
    class="api-form card p-3"
>
    <?= $csrfField() ?>

    <div class="field mb-3">
        <label><?= $e($t('comments.published_in')) ?></label>
        <div>
            <?php if ($contentName !== '' && $adminEditUrl !== ''): ?>
                <a href="<?= $e($url($adminEditUrl)) ?>"><?= $e($contentName) ?></a>
            <?php elseif ($contentName !== ''): ?>
                <span><?= $e($contentName) ?></span>
            <?php else: ?>
                <span><?= $e($t('comments.no_content')) ?></span>
            <?php endif; ?>
            <?php if ($frontUrl !== ''): ?>
                <span class="small text-muted">· <a href="<?= $e($url($frontUrl)) ?>" target="_blank" rel="noopener"><?= $e($url($frontUrl)) ?></a></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="field mb-3">
        <label for="comments-body"><?= $e($t('comments.comment')) ?></label>
        <textarea id="comments-body" name="body" rows="8" required><?= $e($body) ?></textarea>
        <?php if (isset($errors['body'])): ?><small class="text-danger"><?= $e((string)$errors['body']) ?></small><?php endif; ?>
    </div>

    <p class="text-danger" data-api-form-message hidden></p>
</form>
