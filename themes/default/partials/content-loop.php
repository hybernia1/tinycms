<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="content-loop">
    <?php foreach ($items as $loopItem): ?>
        <article class="content-card">
            <?php $thumbnail = trim((string)($loopItem['thumbnail'] ?? '')); ?>
            <a href="<?= $escUrl($contentUrl($loopItem)) ?>" class="content-card-thumb">
                <?php if ($thumbnail !== ''): ?>
                    <img
                        src="<?= $escUrl($mediaUrl($thumbnail, 'small')) ?>"
                        srcset="<?= $escHtml($mediaSrcSet($thumbnail)) ?>"
                        sizes="120px"
                        alt="<?= $escHtml((string)($loopItem['name'] ?? '')) ?>"
                        loading="lazy"
                        decoding="async"
                    >
                <?php endif; ?>
            </a>
            <div class="content-card-body">
                <h2>
                    <a href="<?= $escUrl($contentUrl($loopItem)) ?>"><?= $escHtml((string)($loopItem['name'] ?? '')) ?></a>
                </h2>
                <?php $date = $contentDate($loopItem); ?>
                <?php $author = $contentAuthor($loopItem); ?>
                <?php $authorLink = $authorUrl($loopItem); ?>
                <?php if ($date !== '' || $author !== ''): ?>
                    <p class="text-muted small content-card-meta">
                        <?php if ($date !== ''): ?>
                            <span class="content-card-meta-item"><?= $icon('calendar') ?><span><?= $escHtml($date) ?></span></span>
                        <?php endif; ?>
                        <?php if ($author !== ''): ?>
                            <span class="content-card-meta-item"><?= $icon('users') ?><span><?php if ($authorLink !== ''): ?><a href="<?= $escUrl($authorLink) ?>"><?= $escHtml($author) ?></a><?php else: ?><?= $escHtml($author) ?><?php endif; ?></span></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <p><?= $escHtml((string)($loopItem['excerpt'] ?? '')) ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</section>
