<?php
$totalPages = (int)($pagination['total_pages'] ?? 1);
$page = (int)($pagination['page'] ?? 1);
if ($totalPages <= 1) {
    return;
}
$base = trim((string)($basePath ?? ''), '/');
$buildPageUrl = static function (int $targetPage) use ($url, $base): string {
    $path = $base;
    $suffix = $targetPage > 1 ? '?page=' . $targetPage : '';
    return $url($path . $suffix);
};
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= $e($buildPageUrl($page - 1)) ?>"><?= $e($t('front.prev')) ?></a>
    <?php endif; ?>
    <span><?= $e((string)$page) ?> / <?= $e((string)$totalPages) ?></span>
    <?php if ($page < $totalPages): ?>
        <a href="<?= $e($buildPageUrl($page + 1)) ?>"><?= $e($t('front.next')) ?></a>
    <?php endif; ?>
</nav>
