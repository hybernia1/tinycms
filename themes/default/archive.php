<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<h1><?= $e((string)($archiveLabel ?? $t('front.archive_for'))) ?>: <?= $e((string)($term['name'] ?? '')) ?></h1>
<?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
<?php $basePath = trim((string)($archivePath ?? ''), '/'); ?>
<?php if ($basePath === ''): ?>
    <?php $basePath = trim((string)parse_url($termUrl((array)$term), PHP_URL_PATH), '/'); ?>
<?php endif; ?>
<?php if ($items === []): ?>
    <p><?= $e($t('front.empty')) ?></p>
<?php else: ?>
    <?php $includePartial('content-loop', ['items' => $items]); ?>
    <?php $includePartial('pagination', ['pagination' => $pagination ?? [], 'basePath' => $basePath]); ?>
<?php endif; ?>
