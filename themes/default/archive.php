<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<h1><?= $escHtml((string)($archiveLabel ?? $t('front.archive_for'))) ?>: <?= $escHtml((string)($term['name'] ?? '')) ?></h1>
<?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
<?php $basePath = trim((string)($archivePath ?? ''), '/'); ?>
<?php if ($items === []): ?>
    <p><?= $escHtml($t('front.empty')) ?></p>
<?php else: ?>
    <?php $includePartial('content-loop', ['items' => $items]); ?>
    <?php $includePartial('pagination', ['pagination' => $pagination ?? [], 'basePath' => $basePath]); ?>
<?php endif; ?>
