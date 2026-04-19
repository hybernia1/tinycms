<?php if (($mode ?? 'loop') === 'content' && isset($item) && is_array($item)): ?>
    <article class="content-single">
        <h1><?= $e((string)($item['name'] ?? '')) ?></h1>
        <?= $postThumbnail($item, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
        <div class="content-body"><?= (string)($item['body'] ?? '') ?></div>
    </article>
<?php else: ?>
    <h1><?= $e($t('front.latest_content')) ?></h1>
    <?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
    <?php if ($items === []): ?>
        <p><?= $e($t('front.empty')) ?></p>
    <?php else: ?>
        <?php $includePartial('content-loop', ['items' => $items]); ?>
        <?php $includePartial('pagination', ['pagination' => $pagination ?? [], 'basePath' => '']); ?>
    <?php endif; ?>
<?php endif; ?>
