<section class="theme-block">
    <h1><?= htmlspecialchars($t('front.term.title'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($term['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
</section>

<?php
$loopTitle = $t('front.home.published_posts');
$emptyMessage = $t('front.term.no_posts');
$paginationBasePath = 'term/' . (string)($term['slug'] ?? '');
require __DIR__ . '/parts/post-loop.php';
require __DIR__ . '/parts/pagination.php';
?>
