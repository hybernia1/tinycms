<?php

if (!defined('BASE_DIR')) {
    exit;
}

$item = isset($item) && is_array($item) ? $item : null;
$date = (bool)($show_date ?? true) ? get_content_date($item) : '';
$author = (bool)($show_author ?? true) ? get_author($item) : '';
$commentsCount = (bool)($show_comments_count ?? false) ? get_content_comments_count($item) : null;
$viewsCount = (bool)($show_views_count ?? false) ? get_content_views_count($item) : null;

if ($date === '' && $author === '' && $commentsCount === null && $viewsCount === null) {
    return;
}

?>
<p class="text-muted small content-card-meta">
    <?php if ($date !== ''): ?>
        <span class="content-card-meta-item"><?= icon('calendar') ?><span><?= esc_html($date) ?></span></span>
    <?php endif; ?>
    <?php if ($author !== ''): ?>
        <?php $authorUrl = get_author_url($item); ?>
        <span class="content-card-meta-item">
            <?= icon('users') ?>
            <span>
                <?php if ($authorUrl !== ''): ?>
                    <a href="<?= esc_url($authorUrl) ?>"><?= esc_html($author) ?></a>
                <?php else: ?>
                    <?= esc_html($author) ?>
                <?php endif; ?>
            </span>
        </span>
    <?php endif; ?>
    <?php if ($commentsCount !== null): ?>
        <span class="content-card-meta-item" title="<?= esc_attr(sprintf(t('front.comments_count', '%d comments'), $commentsCount)) ?>">
            <?= icon('comments') ?>
            <span><?= $commentsCount ?></span>
        </span>
    <?php endif; ?>
    <?php if ($viewsCount !== null): ?>
        <span class="content-card-meta-item" title="<?= esc_attr(sprintf(t('front.views_count', '%d views'), $viewsCount)) ?>">
            <?= icon('show') ?>
            <span><?= $viewsCount ?></span>
        </span>
    <?php endif; ?>
</p>
