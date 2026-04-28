<?php

if (!defined('BASE_DIR')) {
    exit;
}

$date = get_date();
$author = get_author();

if ($date === '' && $author === '') {
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
</p>
