<?php
$listModel = is_array($list ?? null) ? $list : [];
$listItems = (array)($listModel['items'] ?? ($pagination['data'] ?? []));
$listPage = max(1, (int)($listModel['page'] ?? ($pagination['page'] ?? 1)));
$listPerPage = (int)($listModel['perPage'] ?? ($pagination['per_page'] ?? \App\Service\Support\PaginationConfig::perPage()));
$listTotalPages = max(1, (int)($listModel['totalPages'] ?? ($pagination['total_pages'] ?? 1)));
$statusCurrent = (string)($listModel['status'] ?? ($status ?? 'all'));
$listQuery = (string)($listModel['query'] ?? ($query ?? ''));
$statusCounts = is_array($listModel['statusCounts'] ?? null) ? $listModel['statusCounts'] : (is_array($statusCounts ?? null) ? $statusCounts : []);
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'unassigned' => $t('terms.status.unassigned') . ' (' . (int)($statusCounts['unassigned'] ?? 0) . ')',
];
$listName = 'terms';
$listEndpoint = $url('admin/api/v1/terms');
$listEditBase = $url('admin/terms/edit?id=');
$searchPlaceholder = $t('terms.search_placeholder');
$listColumns = [
    ['label' => $t('common.name')],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$listAllowedPerPage = is_array($listModel['allowedPerPage'] ?? null) ? $listModel['allowedPerPage'] : ($allowedPerPage ?? []);
$statusEnabled = true;
$deleteConfirmText = $t('terms.delete_confirm');
$statusUrl = static fn(string $targetStatus): string => $url('admin/terms?status=' . urlencode($targetStatus) . '&per_page=' . $listPerPage . '&page=1');
$paginationUrl = static fn(int $targetPage): string => $url('admin/terms?page=' . $targetPage . '&per_page=' . $listPerPage . '&status=' . urlencode($statusCurrent) . '&q=' . urlencode($listQuery));
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

require __DIR__ . '/../partials/list-layout.php';
