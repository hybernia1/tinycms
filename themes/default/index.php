<?php

if (!defined('BASE_DIR')) {
    exit;
}

if (($mode ?? 'loop') === 'content'): ?>
    <article class="content-single">
        <h1><?= esc_html(get_title()) ?></h1>
        <?php include_partial('content-meta'); ?>
        <?php $excerpt = get_excerpt(); ?>
        <?php if ($excerpt !== ''): ?>
            <p class="content-excerpt-lead"><strong><?= esc_html($excerpt) ?></strong></p>
        <?php endif; ?>
        <?= get_thumbnail(['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
        <div class="content-body"><?= get_content() ?></div>
    </article>
<?php else: ?>
    <h1><?= esc_html(t('front.latest_content')) ?></h1>
    <?php if ($items === []): ?>
        <p><?= esc_html(t('front.empty')) ?></p>
    <?php else: ?>
        <?php include_partial('content-loop'); ?>
        <?= get_pagination() ?>
    <?php endif; ?>
<?php endif; ?>
