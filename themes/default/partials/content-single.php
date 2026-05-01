<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <h1><?= esc_html(get_title()) ?></h1>
    <?php include_partial('content-meta'); ?>
    <?php $excerpt = get_excerpt(); ?>
    <?php if ($excerpt !== ''): ?>
        <p class="content-excerpt-lead"><strong><?= esc_html($excerpt) ?></strong></p>
    <?php endif; ?>
    <?= get_thumbnail(['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
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
