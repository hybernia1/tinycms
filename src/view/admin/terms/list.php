<?php
if (!defined('BASE_DIR')) {
    exit;
}

$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = [
    'all' => t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => t('terms.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $formatDateTime): string {
    $id = (int)($row['id'] ?? 0);
    ob_start();
    ?>
    <tr>
        <td>
            <a href="<?= esc_url($url('admin/terms/edit?id=' . $id)) ?>"><?= esc_html((string)($row['name'] ?? '')) ?></a>
            <div class="text-muted small"><?= esc_html($formatDateTime((string)($row['created'] ?? ''))) ?></div>
        </td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="<?= $id ?>" aria-label="<?= esc_attr(t('terms.delete')) ?>" title="<?= esc_attr(t('terms.delete')) ?>">
                <?= icon('delete') ?>
                <span class="sr-only"><?= esc_html(t('terms.delete')) ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = t('terms.search_placeholder');
$list['columns'] = [
    ['label' => t('common.name')],
    ['label' => t('common.actions'), 'class' => 'table-col-actions'],
];
$list['rowRenderer'] = $rowRenderer;

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
