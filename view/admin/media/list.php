<?php
$list = is_array($listBase ?? null) ? $listBase : [];
$statusCurrent = (string)($list['statusCurrent'] ?? 'all');
$listQuery = (string)($list['query'] ?? '');
$statusCounts = is_array($statusCounts ?? null) ? $statusCounts : [];
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => $t('media.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t): string {
    $id = (int)($row['id'] ?? 0);
    $previewPath = trim((string)($row['preview_path'] ?? ''));
    if ($previewPath === '') {
        $previewPath = trim((string)($row['path_webp'] ?? ''));
    }
    if ($previewPath === '') {
        $previewPath = trim((string)($row['path'] ?? ''));
    }
    $previewUrl = $previewPath !== '' ? $url($previewPath) : '';
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
                    <a href="<?= htmlspecialchars($url('admin/media/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                    <div class="text-muted small"><?= htmlspecialchars((string)($row['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($formatDateTime((string)($row['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </td>
        <td class="mobile-hide"><?= htmlspecialchars((string)($row['author_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-media-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('media.delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('media.delete'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= htmlspecialchars($t('media.delete'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = $t('media.search_placeholder');
$list['columns'] = [
    ['label' => $t('admin.menu.media')],
    ['label' => $t('common.author'), 'class' => 'mobile-hide'],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = $t('media.delete_confirm');
$list['rowRenderer'] = $rowRenderer;

require __DIR__ . '/../partials/list-layout.php';
