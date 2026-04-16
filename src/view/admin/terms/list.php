<?php
$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => $t('terms.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t, $e): string {
    $id = (int)($row['id'] ?? 0);
    ob_start();
    ?>
    <tr>
        <td>
            <a href="<?= $e($adminVars['entityEdit']('terms', $id)) ?>"><?= $e((string)($row['name'] ?? '')) ?></a>
            <div class="text-muted small"><?= $e($formatDateTime((string)($row['created'] ?? ''))) ?></div>
        </td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="<?= $id ?>" aria-label="<?= $e($t('terms.delete')) ?>" title="<?= $e($t('terms.delete')) ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= $e($t('terms.delete')) ?></span>
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
