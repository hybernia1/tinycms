<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="content-loop">
    <?php foreach ($items as $loopItem): ?>
        <article class="content-card">
            <a href="<?= esc_url(get_permalink($loopItem)) ?>" class="content-card-thumb">
                <?= get_thumbnail($loopItem, ['size' => 'small', 'sizes' => '120px', 'class' => '', 'wrap' => false]) ?>
            </a>
            <div class="content-card-body">
                <h2>
                    <a href="<?= esc_url(get_permalink($loopItem)) ?>"><?= esc_html(get_title($loopItem)) ?></a>
                </h2>
                <?= get_content_meta($loopItem) ?>
                <?php $excerpt = get_excerpt($loopItem); ?>
                <?php if ($excerpt !== ''): ?>
                    <p><?= esc_html($excerpt) ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
