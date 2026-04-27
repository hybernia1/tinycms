<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <h1><?= esc_html(get_title($item)) ?></h1>
    <?= get_content_meta($item) ?>
    <?php $excerpt = get_excerpt($item); ?>
    <?php if ($excerpt !== ''): ?>
        <p class="content-excerpt-lead"><strong><?= esc_html($excerpt) ?></strong></p>
    <?php endif; ?>
    <?= get_thumbnail($item, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
    <?= get_term_links($item) ?>
    <div class="content-body"><?= get_content($item) ?></div>
</article>
