<div class="container py-5">
    <article class="card p-4 mb-4">
        <h1 class="mb-3"><?= htmlspecialchars($t('front.term.title'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($term['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
    </article>

    <?php
    $loopTitle = $t('front.home.published_posts');
    $emptyMessage = $t('front.term.no_posts');
    require __DIR__ . '/parts/post-loop.php';

    $currentPage = (int)($pagination['page'] ?? 1);
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    $termSlug = (string)($term['slug'] ?? '');
    ?>

    <?php if ($totalPages > 1): ?>
        <nav class="d-flex gap-2 mt-4">
            <?php if ($currentPage > 1): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('term/' . $termSlug . ($currentPage - 1 > 1 ? '?page=' . ($currentPage - 1) : '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('term/' . $termSlug . '?page=' . ($currentPage + 1)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
