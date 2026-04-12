<div class="theme-page">
    <article class="theme-panel">
        <h1><?= htmlspecialchars($t('front.search.title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <form method="get" action="<?= htmlspecialchars($url('search'), ENT_QUOTES, 'UTF-8') ?>" class="theme-inline-search">
            <input type="search" name="q" value="<?= htmlspecialchars((string)$query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('front.search.placeholder'), ENT_QUOTES, 'UTF-8') ?>" required>
            <button type="submit"><?= htmlspecialchars($t('front.search.submit'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </article>

    <?php
    $loopTitle = $query === ''
        ? $t('front.search.title')
        : $t('front.search.results_for') . ': ' . $query;
    $emptyMessage = $t('front.search.no_results');
    require __DIR__ . '/parts/post-loop.php';

    $currentPage = (int)($pagination['page'] ?? 1);
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    $queryParam = rawurlencode((string)$query);
    ?>

    <?php if ($totalPages > 1): ?>
        <nav class="theme-pagination">
            <?php if ($currentPage > 1): ?>
                <a href="<?= htmlspecialchars($url('search?q=' . $queryParam . '&page=' . ($currentPage - 1)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= htmlspecialchars($url('search?q=' . $queryParam . '&page=' . ($currentPage + 1)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
