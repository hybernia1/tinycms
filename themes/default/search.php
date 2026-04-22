<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<h1><?= esc_html(t('front.search_results')) ?></h1>
<?php $queryText = trim((string)($query ?? '')); ?>
<?php if ($queryText !== ''): ?>
    <p class="text-muted small"><?= esc_html(t('front.search_results_for')) ?>: <?= esc_html($queryText) ?></p>
<?php endif; ?>
<?php $items = (array)(($pagination ?? [])['data'] ?? []); ?>
<?php if ($items === []): ?>
    <p><?= esc_html(t('front.empty')) ?></p>
<?php else: ?>
    <?php $includePartial('content-loop', ['items' => $items]); ?>
    <?php $basePath = 'search'; ?>
    <?php if ($queryText !== ''): ?>
        <?php $basePath .= '?q=' . rawurlencode($queryText); ?>
    <?php endif; ?>
    <?php $includePartial('pagination', ['pagination' => $pagination ?? [], 'basePath' => $basePath]); ?>
<?php endif; ?>
