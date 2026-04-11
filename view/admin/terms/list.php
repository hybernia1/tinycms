<?php
$list = is_array($listBase ?? null) ? $listBase : [];
$statusCounts = is_array($statusCounts ?? null) ? $statusCounts : [];
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => $t('terms.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t): string {
    $id = (int)($row['id'] ?? 0);
    ob_start();
    ?>
    <tr>
        <td>
            <a href="<?= htmlspecialchars($url('admin/terms/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
            <div class="text-muted small"><?= htmlspecialchars($formatDateTime((string)($row['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('terms.delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('terms.delete'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= htmlspecialchars($t('terms.delete'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = $t('terms.search_placeholder');
$list['columns'] = [
    ['label' => $t('common.name')],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = $t('terms.delete_confirm');
$list['rowRenderer'] = $rowRenderer;

require __DIR__ . '/../partials/list-layout.php';
