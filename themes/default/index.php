<?php

if (!defined('BASE_DIR')) {
    exit;
}

if (($mode ?? 'loop') === 'content' && isset($item) && is_array($item)): ?>
    <article class="content-single">
        <h1><?= $escHtml((string)($item['name'] ?? '')) ?></h1>
        <?php $date = $contentDate($item); ?>
        <?php $author = $contentAuthor($item); ?>
        <?php $authorLink = $authorUrl($item); ?>
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
        <?php $excerpt = trim((string)($item['excerpt'] ?? '')); ?>
        <?php if ($excerpt !== ''): ?>
            <p class="content-excerpt-lead"><strong><?= $escHtml($excerpt) ?></strong></p>
        <?php endif; ?>
        <?= $contentThumbnail($item, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
        <div class="content-body"><?= (string)($item['body'] ?? '') ?></div>
    </article>
<?php else: ?>
    <h1><?= $escHtml($t('front.latest_content')) ?></h1>
    <?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
    <?php if ($items === []): ?>
        <p><?= $escHtml($t('front.empty')) ?></p>
    <?php else: ?>
        <?php $includePartial('content-loop', ['items' => $items]); ?>
        <?php $includePartial('pagination', ['pagination' => $pagination ?? [], 'basePath' => '']); ?>
    <?php endif; ?>
<?php endif; ?>
