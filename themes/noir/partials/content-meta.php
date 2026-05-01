<?php

if (!defined('BASE_DIR')) {
    exit;
}

$date = get_date();
$author = get_author();
$commentsCount = (bool)($show_comments_count ?? false) ? get_comments_count() : 0;

if ($date === '' && $author === '' && $commentsCount <= 0) {
    return;
}

?>
<p class="text-muted small content-card-meta">
    <?php if ($date !== ''): ?>
        <span class="content-card-meta-item"><?= icon('calendar') ?><span><?= esc_html($date) ?></span></span>
    <?php endif; ?>
    <?php if ($author !== ''): ?>
        <?php $authorUrl = get_author_url(); ?>
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
    <?php if ($commentsCount > 0): ?>
        <span class="content-card-meta-item" title="<?= esc_attr(sprintf(t('front.comments_count', '%d comments'), $commentsCount)) ?>">
            <?= icon('comments') ?>
            <span><?= $commentsCount ?></span>
        </span>
    <?php endif; ?>
</p>
