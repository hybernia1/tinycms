<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="content-loop">
    <?php foreach ($items as $content): ?>
        <?php use_content_item($content); ?>
        <?php $permalink = get_permalink(); ?>
        <?php $thumbnail = get_thumbnail(['size' => 'small', 'sizes' => '160px', 'class' => '', 'wrap' => false]); ?>
        <article class="content-card">
            <?php if ($thumbnail !== ''): ?>
                <a href="<?= esc_url($permalink) ?>" class="content-card-thumb"><?= $thumbnail ?></a>
            <?php endif; ?>
            <div class="content-card-body">
                <h2>
                    <a href="<?= esc_url($permalink) ?>"><?= esc_html(get_title()) ?></a>
                </h2>
                <?php include_partial('content-meta', ['show_comments_count' => true]); ?>
                <?php $excerpt = get_excerpt(); ?>
                <?php if ($excerpt !== ''): ?>
                    <p><?= esc_html($excerpt) ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
