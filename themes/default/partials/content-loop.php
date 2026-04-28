<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="content-loop">
    <?php foreach ($items as $content): ?>
        <?php use_content_item($content); ?>
        <?php $permalink = get_permalink(); ?>
        <article class="content-card">
            <a href="<?= esc_url($permalink) ?>" class="content-card-thumb">
                <?= get_thumbnail(['size' => 'small', 'sizes' => '120px', 'class' => '', 'wrap' => false]) ?>
            </a>
            <div class="content-card-body">
                <h2>
                    <a href="<?= esc_url($permalink) ?>"><?= esc_html(get_title()) ?></a>
                </h2>
                <?php include_partial('content-meta'); ?>
                <?php $excerpt = get_excerpt(); ?>
                <?php if ($excerpt !== ''): ?>
                    <p><?= esc_html($excerpt) ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
