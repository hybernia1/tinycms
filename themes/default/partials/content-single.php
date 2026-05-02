<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <h1><?= esc_html(get_content_title()) ?></h1>
    <?php include_partial('content-meta', [
        'show_date' => theme_option_enabled('single_meta_date', true),
        'show_author' => theme_option_enabled('single_meta_author', true),
        'show_comments_count' => theme_option_enabled('single_meta_comments'),
    ]); ?>
    <?php $excerpt = get_content_excerpt(); ?>
    <?php if ($excerpt !== ''): ?>
        <p class="content-excerpt-lead"><strong><?= esc_html($excerpt) ?></strong></p>
    <?php endif; ?>
    <?php if (theme_option_enabled('single_show_thumbnail', true)): ?>
        <?= get_content_thumbnail(null, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
    <?php endif; ?>
    <?php $terms = (($show_terms ?? true) === true && theme_option_enabled('single_show_terms', true)) ? get_content_terms() : []; ?>
    <?php if ($terms !== []): ?>
        <ul class="term-list">
            <?php foreach ($terms as $term): ?>
                <?php $name = trim((string)($term['name'] ?? '')); ?>
                <?php $termUrl = get_term_url($term); ?>
                <?php if ($name !== '' && $termUrl !== ''): ?>
                    <li><a href="<?= esc_url($termUrl) ?>"><?= esc_html($name) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <div class="content-body"><?= get_content_body() ?></div>
    <?php if (content_comments_enabled()): ?>
        <section id="comments" class="comments">
            <?= get_content_comments() ?>
            <?= get_content_comments_form() ?>
        </section>
    <?php endif; ?>
</article>
