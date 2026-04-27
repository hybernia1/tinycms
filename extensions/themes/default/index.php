<?php

if (!defined('BASE_DIR')) {
    exit;
}

if (($mode ?? 'loop') === 'content' && isset($item) && is_array($item)): ?>
    <article class="content-single">
        <h1><?= esc_html(get_title($item)) ?></h1>
        <?= get_content_meta($item) ?>
        <?php $excerpt = get_excerpt($item); ?>
        <?php if ($excerpt !== ''): ?>
            <p class="content-excerpt-lead"><strong><?= esc_html($excerpt) ?></strong></p>
        <?php endif; ?>
        <?= get_thumbnail($item, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
        <div class="content-body"><?= get_content($item) ?></div>
    </article>
<?php else: ?>
    <h1><?= esc_html(t('front.latest_content')) ?></h1>
    <?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
    <?php if ($items === []): ?>
        <p><?= esc_html(t('front.empty')) ?></p>
    <?php else: ?>
        <?php $includePartial('content-loop', ['items' => $items]); ?>
        <?= get_pagination($pagination ?? []) ?>
    <?php endif; ?>
<?php endif; ?>
