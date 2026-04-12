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

    $totalPages = (int)($pagination['total_pages'] ?? 1);
    $pager = $themePagination($pagination, 'search', ['q' => $query]);
    ?>

    <?php if ($totalPages > 1): ?>
        <nav class="theme-pagination">
            <?php if ($pager['previous'] !== ''): ?>
                <a href="<?= htmlspecialchars((string)$pager['previous'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <?php if ($pager['next'] !== ''): ?>
                <a href="<?= htmlspecialchars((string)$pager['next'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
