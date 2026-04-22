<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="content-loop">
    <?php foreach ($items as $loopItem): ?>
        <article class="content-card">
            <?php $thumbnail = trim((string)($loopItem['thumbnail'] ?? '')); ?>
            <a href="<?= esc_url($contentUrl($loopItem)) ?>" class="content-card-thumb">
                <?php if ($thumbnail !== ''): ?>
                    <img
                        src="<?= esc_url($mediaUrl($thumbnail, 'small')) ?>"
                        srcset="<?= esc_attr($mediaSrcSet($thumbnail)) ?>"
                        sizes="120px"
                        alt="<?= esc_attr((string)($loopItem['name'] ?? '')) ?>"
                        loading="lazy"
                        decoding="async"
                    >
                <?php endif; ?>
            </a>
            <div class="content-card-body">
                <h2>
                    <a href="<?= esc_url($contentUrl($loopItem)) ?>"><?= esc_html((string)($loopItem['name'] ?? '')) ?></a>
                </h2>
                <?php $date = $contentDate($loopItem); ?>
                <?php $author = $contentAuthor($loopItem); ?>
                <?php $authorLink = $authorUrl($loopItem); ?>
                <?php if ($date !== '' || $author !== ''): ?>
                    <p class="text-muted small content-card-meta">
                        <?php if ($date !== ''): ?>
                            <span class="content-card-meta-item"><?= icon('calendar') ?><span><?= esc_html($date) ?></span></span>
                        <?php endif; ?>
                        <?php if ($author !== ''): ?>
                            <span class="content-card-meta-item"><?= icon('users') ?><span><?php if ($authorLink !== ''): ?><a href="<?= esc_url($authorLink) ?>"><?= esc_html($author) ?></a><?php else: ?><?= esc_html($author) ?><?php endif; ?></span></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <p><?= esc_html((string)($loopItem['excerpt'] ?? '')) ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</section>
