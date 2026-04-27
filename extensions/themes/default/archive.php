<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<h1><?= esc_html((string)($archiveLabel ?? t('front.archive_for'))) ?>: <?= esc_html((string)($term['name'] ?? '')) ?></h1>
<?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
<?php $basePath = trim((string)($archivePath ?? ''), '/'); ?>
<?php if ($items === []): ?>
    <p><?= esc_html(t('front.empty')) ?></p>
<?php else: ?>
    <?php $includePartial('content-loop', ['items' => $items]); ?>
    <?= get_pagination($pagination ?? [], $basePath) ?>
<?php endif; ?>
