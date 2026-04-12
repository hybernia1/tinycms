<div class="theme-page">
    <?php
    $loopTitle = $t('front.home.published_posts');
    $emptyMessage = $t('front.home.no_posts');
    require __DIR__ . '/parts/post-loop.php';

    $totalPages = (int)(($pagination['total_pages'] ?? 1));
    $pager = $themePagination((array)$pagination, '/');
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
