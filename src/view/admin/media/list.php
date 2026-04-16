<?php
$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => $t('media.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t, $e): string {
    $id = (int)($row['id'] ?? 0);
    $previewPath = trim((string)($row['preview_path'] ?? ''));
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
                        <img src="<?= $e($previewUrl) ?>" alt="<?= $e((string)($row['name'] ?? '')) ?>">
                    </div>
                <?php else: ?>
                    <div class="media-list-thumb media-list-thumb-empty"></div>
                <?php endif; ?>
                <div>
                    <a href="<?= $e($url('admin/media/edit?id=' . $id)) ?>"><?= $e((string)($row['name'] ?? '')) ?></a>
                    <div class="text-muted small"><?= $e((string)($row['path'] ?? '')) ?></div>
                    <div class="text-muted small"><?= $e($formatDateTime((string)($row['created'] ?? ''))) ?></div>
                </div>
            </div>
        </td>
        <td class="mobile-hide"><?= $e((string)($row['author_name'] ?? '—')) ?></td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-media-delete-open="<?= $id ?>" aria-label="<?= $e($t('media.delete')) ?>" title="<?= $e($t('media.delete')) ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= $e($t('media.delete')) ?></span>
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
