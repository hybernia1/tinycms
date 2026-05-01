<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <header class="content-single-head">
        <p class="eyebrow"><?= esc_html(t('front.noir_story')) ?></p>
        <h1><?= esc_html(get_title()) ?></h1>
        <?php include_partial('content-meta'); ?>
    </header>
    <?php $excerpt = get_excerpt(); ?>
    <?php if ($excerpt !== ''): ?>
        <p class="content-excerpt-lead"><?= esc_html($excerpt) ?></p>
    <?php endif; ?>
    <?= get_thumbnail(['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager', 'class' => 'content-cover']) ?>
    <?php if (($show_terms ?? true) === true): ?>
        <?= get_term_links() ?>
    <?php endif; ?>
    <div class="content-body"><?= get_content() ?></div>
    <?php if (comments_enabled()): ?>
        <section id="comments" class="comments">
            <?= get_comments_list() ?>
            <?= get_comments_form() ?>
        </section>
    <?php endif; ?>
</article>
