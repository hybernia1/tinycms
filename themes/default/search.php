<div class="container py-5">
    <article class="card p-4 mb-4">
        <h1 class="mb-3"><?= htmlspecialchars($t('front.search.title', 'Search'), ENT_QUOTES, 'UTF-8') ?></h1>
        <form method="get" action="<?= htmlspecialchars($url('search'), ENT_QUOTES, 'UTF-8') ?>" class="d-flex gap-2">
            <input type="search" name="q" value="<?= htmlspecialchars((string)$query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('front.search.placeholder', 'Search published content...'), ENT_QUOTES, 'UTF-8') ?>" required>
            <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('front.search.submit', 'Search'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </article>

    <?php
    $loopTitle = $query === ''
        ? $t('front.search.title', 'Search')
        : $t('front.search.results_for', 'Search results for') . ': ' . $query;
    $emptyMessage = $t('front.search.no_results', 'No matching published content found.');
    require __DIR__ . '/parts/post-loop.php';

    $currentPage = (int)($pagination['page'] ?? 1);
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    $queryParam = rawurlencode((string)$query);
    ?>

    <?php if ($totalPages > 1): ?>
        <nav class="d-flex gap-2 mt-4">
            <?php if ($currentPage > 1): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('search?q=' . $queryParam . '&page=' . ($currentPage - 1)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('search?q=' . $queryParam . '&page=' . ($currentPage + 1)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
