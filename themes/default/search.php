<section class="theme-block">
    <h1><?= htmlspecialchars($t('front.search.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ((string)$query !== ''): ?>
        <p class="theme-muted"><?= htmlspecialchars($t('front.search.results_for'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)$query, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</section>

<?php
$loopTitle = $query === ''
    ? $t('front.search.title')
    : $t('front.search.results_for') . ': ' . $query;
$emptyMessage = $t('front.search.no_results');
$paginationBasePath = 'search?q=' . rawurlencode((string)$query);
require __DIR__ . '/parts/post-loop.php';
require __DIR__ . '/parts/pagination.php';
?>
