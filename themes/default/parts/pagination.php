<?php
$currentPage = (int)($pagination['page'] ?? 1);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$paginationBasePath = trim((string)($paginationBasePath ?? ''));

$buildPaginationUrl = static function (int $targetPage) use ($paginationBasePath, $url): string {
    if ($targetPage <= 1) {
        return $url($paginationBasePath);
    }

    if ($paginationBasePath === '') {
        return $url('?page=' . $targetPage);
    }

    $separator = str_contains($paginationBasePath, '?') ? '&' : '?';
    return $url($paginationBasePath . $separator . 'page=' . $targetPage);
};
?>
<?php if ($totalPages > 1): ?>
    <nav class="theme-pagination" aria-label="Pagination">
        <a class="theme-pagination-link<?= $currentPage <= 1 ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($buildPaginationUrl($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></a>
        <span class="theme-pagination-state"><?= $currentPage ?> / <?= $totalPages ?></span>
        <a class="theme-pagination-link<?= $currentPage >= $totalPages ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($buildPaginationUrl($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></a>
    </nav>
<?php endif; ?>
