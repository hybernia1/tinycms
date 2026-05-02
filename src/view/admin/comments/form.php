<?php
if (!defined('BASE_DIR')) {
    exit;
}

$contentId = (int)($item['content'] ?? 0);
$statusValue = (string)($item['status'] ?? 'draft');
$children = is_array($children ?? null) ? $children : [];
$author = trim((string)($item['author_name'] ?? ''));
if ($author === '') {
    $author = trim((string)($item['author_email'] ?? ''));
}
?>
<form
    id="comments-form"
    class="comment-editor-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/comments/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    data-stay-on-page
>
    <?= $csrfField() ?>
    <input type="hidden" name="status" value="<?= esc_attr($statusValue) ?>">
    <section class="card p-4 comment-editor-card">
        <div class="comment-editor-main">
            <div class="comment-editor-head">
                <span class="comment-editor-icon"><?= icon('comments') ?></span>
                <div class="comment-editor-title">
                    <div class="comment-editor-meta">
                        <span><?= esc_html($author !== '' ? $author : t('common.no_author')) ?></span>
                        <span><?= esc_html(t('comments.statuses.' . $statusValue, ucfirst($statusValue))) ?></span>
                        <span><?= esc_html($formatDateTime((string)($item['created'] ?? ''))) ?></span>
                        <?php if ((string)($item['ip_address'] ?? '') !== ''): ?>
                            <span><?= esc_html((string)($item['ip_address'] ?? '')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($contentId > 0): ?>
                        <div class="comment-editor-meta comment-editor-meta-sub">
                            <a href="<?= esc_url($url('admin/content/edit?id=' . $contentId)) ?>"><?= esc_html((string)($item['content_name'] ?? ('#' . $contentId))) ?></a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="comment-editor-actions">
                    <?php if ($statusValue === 'trash'): ?>
                        <button class="btn btn-light btn-icon" type="submit" formaction="<?= esc_url($url('admin/api/v1/comments/' . (int)($item['id'] ?? 0) . '/restore')) ?>" formnovalidate data-api-follow-redirect aria-label="<?= esc_attr(t('comments.restore')) ?>" title="<?= esc_attr(t('comments.restore')) ?>">
                            <?= icon('restore') ?>
                            <span class="sr-only"><?= esc_html(t('comments.restore')) ?></span>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-light btn-icon" type="submit" formaction="<?= esc_url($url('admin/api/v1/comments/' . (int)($item['id'] ?? 0) . '/status')) ?>" name="mode" value="<?= esc_attr($statusValue === 'published' ? 'draft' : 'publish') ?>" formnovalidate data-api-follow-redirect aria-label="<?= esc_attr($statusValue === 'published' ? t('comments.switch_to_draft') : t('comments.publish')) ?>" title="<?= esc_attr($statusValue === 'published' ? t('comments.switch_to_draft') : t('comments.publish')) ?>">
                            <?= icon($statusValue === 'published' ? 'hide' : 'show') ?>
                            <span class="sr-only"><?= esc_html($statusValue === 'published' ? t('comments.switch_to_draft') : t('comments.publish')) ?></span>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-light btn-icon" type="submit" formaction="<?= esc_url($url('admin/api/v1/comments/' . (int)($item['id'] ?? 0) . '/delete')) ?>" formnovalidate data-api-follow-redirect aria-label="<?= esc_attr(t('comments.delete')) ?>" title="<?= esc_attr(t('comments.delete')) ?>">
                        <?= icon('delete') ?>
                        <span class="sr-only"><?= esc_html(t('comments.delete')) ?></span>
                    </button>
                    <button class="btn btn-primary btn-icon" type="submit" aria-label="<?= esc_attr(t('common.save')) ?>" title="<?= esc_attr(t('common.save')) ?>">
                        <?= icon('save') ?>
                        <span class="sr-only"><?= esc_html(t('common.save')) ?></span>
                    </button>
                </div>
            </div>
            <div class="m-0">
                <label><?= esc_html(t('comments.body')) ?></label>
                <textarea class="comment-thread-textarea" name="body" rows="7" required><?= esc_html((string)($item['body'] ?? '')) ?></textarea>
                <?php if (!empty($errors['body'])): ?><small class="text-danger"><?= esc_html((string)$errors['body']) ?></small><?php endif; ?>
            </div>
        </div>
    </section>
</form>

<section class="comment-thread-replies mt-4">
    <div class="comment-section-head">
        <div>
            <h2 class="m-0"><?= esc_html(t('comments.replies')) ?></h2>
        </div>
        <span class="badge"><?= count($children) ?></span>
    </div>
    <?php if ($children === []): ?>
        <div class="card p-3 text-muted"><?= esc_html(t('comments.no_replies')) ?></div>
    <?php endif; ?>
    <?php foreach ($children as $child): ?>
        <?php
        $childId = (int)($child['id'] ?? 0);
        $childStatus = (string)($child['status'] ?? 'draft');
        $childAuthor = trim((string)($child['author_name'] ?? ''));
        if ($childAuthor === '') {
            $childAuthor = trim((string)($child['author_email'] ?? ''));
        }
        ?>
        <form
            id="comments-child-form-<?= $childId ?>"
            class="mb-3 comment-editor-form"
            method="post"
            action="<?= esc_url($url('admin/api/v1/comments/' . $childId . '/edit')) ?>"
            data-api-submit
            data-stay-on-page
        >
            <?= $csrfField() ?>
            <input type="hidden" name="status" value="<?= esc_attr($childStatus) ?>">
            <div class="card p-4 comment-editor-card comment-editor-card-reply">
                <div class="comment-editor-main">
                    <div class="comment-editor-head">
                        <span class="comment-editor-icon"><?= icon('reply') ?></span>
                        <div class="comment-editor-title">
                            <div class="comment-editor-meta">
                                <span><?= esc_html($childAuthor !== '' ? $childAuthor : t('common.no_author')) ?></span>
                                <span><?= esc_html(t('comments.statuses.' . $childStatus, ucfirst($childStatus))) ?></span>
                                <span><?= esc_html($formatDateTime((string)($child['created'] ?? ''))) ?></span>
                                <?php if ((string)($child['ip_address'] ?? '') !== ''): ?>
                                    <span><?= esc_html((string)($child['ip_address'] ?? '')) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="comment-editor-actions">
                            <?php if ($childStatus === 'trash'): ?>
                                <button class="btn btn-light btn-icon" type="submit" formaction="<?= esc_url($url('admin/api/v1/comments/' . $childId . '/restore')) ?>" formnovalidate data-api-follow-redirect aria-label="<?= esc_attr(t('comments.restore')) ?>" title="<?= esc_attr(t('comments.restore')) ?>">
                                    <?= icon('restore') ?>
                                    <span class="sr-only"><?= esc_html(t('comments.restore')) ?></span>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-light btn-icon" type="submit" formaction="<?= esc_url($url('admin/api/v1/comments/' . $childId . '/status')) ?>" name="mode" value="<?= esc_attr($childStatus === 'published' ? 'draft' : 'publish') ?>" formnovalidate data-api-follow-redirect aria-label="<?= esc_attr($childStatus === 'published' ? t('comments.switch_to_draft') : t('comments.publish')) ?>" title="<?= esc_attr($childStatus === 'published' ? t('comments.switch_to_draft') : t('comments.publish')) ?>">
                                    <?= icon($childStatus === 'published' ? 'hide' : 'show') ?>
                                    <span class="sr-only"><?= esc_html($childStatus === 'published' ? t('comments.switch_to_draft') : t('comments.publish')) ?></span>
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-light btn-icon" type="submit" formaction="<?= esc_url($url('admin/api/v1/comments/' . $childId . '/delete')) ?>" formnovalidate data-api-follow-redirect aria-label="<?= esc_attr(t('comments.delete')) ?>" title="<?= esc_attr(t('comments.delete')) ?>">
                                <?= icon('delete') ?>
                                <span class="sr-only"><?= esc_html(t('comments.delete')) ?></span>
                            </button>
                            <button class="btn btn-primary btn-icon" type="submit" aria-label="<?= esc_attr(t('common.save')) ?>" title="<?= esc_attr(t('common.save')) ?>">
                                <?= icon('save') ?>
                                <span class="sr-only"><?= esc_html(t('common.save')) ?></span>
                            </button>
                        </div>
                    </div>
                    <div class="m-0">
                        <label><?= esc_html(t('comments.body')) ?></label>
                        <textarea name="body" rows="4" required><?= esc_html((string)($child['body'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </form>
    <?php endforeach; ?>
</section>
