<?php
$listItems = $pagination['data'] ?? [];
$listPage = (int)($pagination['page'] ?? 1);
$listPerPage = (int)($pagination['per_page'] ?? \App\Service\Support\PaginationConfig::perPage());
$listTotalPages = (int)($pagination['total_pages'] ?? 1);
$statusCurrent = (string)($status ?? 'all');
$listQuery = (string)($query ?? '');
$statusCounts = is_array($statusCounts ?? null) ? $statusCounts : [];
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => $t('media.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$thumbSuffix = '_100x100.webp';
if (defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS)) {
    $firstVariant = MEDIA_THUMB_VARIANTS[0] ?? null;
    if (is_array($firstVariant) && !empty($firstVariant['suffix'])) {
        $thumbSuffix = (string)$firstVariant['suffix'];
    }
}
$authUser = $_SESSION['auth'] ?? [];
$isEditor = (string)($authUser['role'] ?? '') === 'editor';
$currentUserId = (int)($authUser['id'] ?? 0);
$csrfMarkup = $csrfField();
$listName = 'media';
$listEndpoint = $url('admin/api/v1/media');
$listEditBase = $url('admin/media/edit?id=');
$listRootAttrs = ['data-thumb-suffix' => $thumbSuffix];
$searchPlaceholder = $t('media.search_placeholder');
$searchHidden = ['status' => $statusCurrent, 'per_page' => (string)$listPerPage, 'page' => '1'];
$perPageHidden = ['status' => $statusCurrent, 'q' => $listQuery, 'page' => '1'];
$listColumns = [
    ['label' => $t('admin.menu.media')],
    ['label' => $t('common.author'), 'class' => 'mobile-hide'],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$listAllowedPerPage = $allowedPerPage;
$statusEnabled = true;
$deleteConfirmText = $t('media.delete_confirm');
$statusUrl = static fn(string $targetStatus): string => $url('admin/media?status=' . urlencode($targetStatus) . '&per_page=' . $listPerPage . '&page=1');
$paginationUrl = static fn(int $targetPage): string => $url('admin/media?page=' . $targetPage . '&per_page=' . $listPerPage . '&status=' . urlencode($statusCurrent) . '&q=' . urlencode($listQuery));
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t, $isEditor, $currentUserId, $thumbSuffix): string {
    $id = (int)($row['id'] ?? 0);
    $previewPath = trim((string)($row['path_webp'] ?? ''));
    if ($previewPath !== '') {
        $previewPath = (string)(preg_replace('/\.webp$/i', $thumbSuffix, $previewPath) ?? $previewPath);
    } else {
        $previewPath = trim((string)($row['path'] ?? ''));
    }
    $previewUrl = $previewPath !== '' ? $url($previewPath) : '';
    $canManage = !$isEditor || (int)($row['author'] ?? 0) === $currentUserId;
    ob_start();
    ?>
    <tr>
        <td>
            <div class="d-flex align-center gap-2">
                <?php if ($previewUrl !== ''): ?>
                    <div class="media-list-thumb">
                        <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php else: ?>
                    <div class="media-list-thumb media-list-thumb-empty"></div>
                <?php endif; ?>
                <div>
                    <?php if ($canManage): ?>
                        <a href="<?= htmlspecialchars($url('admin/media/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php else: ?>
                        <span><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <div class="text-muted small"><?= htmlspecialchars((string)($row['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($formatDateTime((string)($row['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </td>
        <td class="mobile-hide"><?= htmlspecialchars((string)($row['author_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="table-col-actions">
            <?php if ($canManage): ?>
                <button class="btn btn-light btn-icon" type="button" data-media-delete-open="<?= $id ?>" data-modal-open data-modal-target="#media-delete-modal" aria-label="<?= htmlspecialchars($t('media.delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('media.delete'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('delete') ?>
                    <span class="sr-only"><?= htmlspecialchars($t('media.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};

require __DIR__ . '/../partials/list-layout.php';
