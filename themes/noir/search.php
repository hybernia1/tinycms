<?php

if (!defined('BASE_DIR')) {
    exit;
}

$queryText = trim((string)($query ?? ''));
?>
<header class="page-heading">
    <p class="eyebrow"><?= esc_html(t('front.search_results')) ?></p>
    <h1><?= $queryText !== '' ? esc_html($queryText) : esc_html(t('front.search_results')) ?></h1>
</header>
<?php if ($queryText !== ''): ?>
    <p class="text-muted small"><?= esc_html(t('front.search_results_for')) ?>: <?= esc_html($queryText) ?></p>
<?php endif; ?>
<?php if ($items === []): ?>
    <p><?= esc_html(t('front.empty')) ?></p>
<?php else: ?>
    <?php include_partial('content-loop'); ?>
    <?= get_pagination() ?>
<?php endif; ?>
