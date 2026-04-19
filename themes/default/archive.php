<h1><?= $e($t('front.archive_for')) ?>: <?= $e((string)($term['name'] ?? '')) ?></h1>
<?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
<?php if ($items === []): ?>
    <p><?= $e($t('front.empty')) ?></p>
<?php else: ?>
    <?php $includePartial('content-loop', ['items' => $items]); ?>
    <?php $includePartial('pagination', ['pagination' => $pagination ?? [], 'basePath' => 'term/' . (int)($term['id'] ?? 0)]); ?>
<?php endif; ?>
