<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <h1><?= $e((string)($item['name'] ?? '')) ?></h1>
    <?php $date = $contentDate($item); ?>
    <?php $author = $contentAuthor($item); ?>
    <?php $authorLink = $authorUrl($item); ?>
    <?php if ($date !== '' || $author !== ''): ?>
        <p class="text-muted small content-card-meta">
            <?php if ($date !== ''): ?>
                <span class="content-card-meta-item"><?= $icon('calendar') ?><span><?= $e($date) ?></span></span>
            <?php endif; ?>
            <?php if ($author !== ''): ?>
                <span class="content-card-meta-item"><?= $icon('users') ?><span><?php if ($authorLink !== ''): ?><a href="<?= $e($authorLink) ?>"><?= $e($author) ?></a><?php else: ?><?= $e($author) ?><?php endif; ?></span></span>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <?php $excerpt = trim((string)($item['excerpt'] ?? '')); ?>
    <?php if ($excerpt !== ''): ?>
        <p class="content-excerpt-lead"><strong><?= $e($excerpt) ?></strong></p>
    <?php endif; ?>
    <?= $contentThumbnail($item, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
    <?php $terms = (array)($item['terms'] ?? []); ?>
    <?php if ($terms !== []): ?>
        <ul class="term-list">
            <?php foreach ($terms as $term): ?>
                <li><a href="<?= $e($termUrl($term)) ?>"><?= $e((string)($term['name'] ?? '')) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <div class="content-body"><?= (string)($item['body'] ?? '') ?></div>
</article>
