<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="content-loop">
    <?php foreach ($items as $content): ?>
        <?php $contentUrl = get_content_url($content); ?>
        <?php $thumbnail = theme_option_enabled('archive_show_thumbnail', true) ? get_content_thumbnail($content, ['size' => 'small', 'sizes' => '160px', 'class' => '', 'wrap' => false]) : ''; ?>
        <article class="content-card">
            <?php if ($thumbnail !== ''): ?>
                <a href="<?= esc_url($contentUrl) ?>" class="content-card-thumb"><?= $thumbnail ?></a>
            <?php endif; ?>
            <div class="content-card-body">
                <h2>
                    <a href="<?= esc_url($contentUrl) ?>"><?= esc_html(get_content_title($content)) ?></a>
                </h2>

                <?php include_partial('content-meta', [
                    'item' => $content,
                    'show_date' => theme_option_enabled('archive_meta_date', true),
                    'show_author' => theme_option_enabled('archive_meta_author', true),
                    'show_comments_count' => theme_option_enabled('archive_meta_comments', true),
                ]); ?>
                <?php $excerpt = get_content_excerpt($content); ?>
                <?php if ($excerpt !== ''): ?>
                    <p><?= esc_html($excerpt) ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
