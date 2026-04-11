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

$listConfig = [
    'meta' => [
        'name' => 'terms',
        'endpoint' => $url('admin/api/v1/terms'),
        'editBase' => $url('admin/terms/edit?id='),
        'csrfMarkup' => $csrfField(),
    ],
    'filters' => [
        'statusEnabled' => true,
        'statusLinks' => $statusLinks,
        'statusCurrent' => $statusCurrent,
        'statusUrl' => static fn(string $targetStatus): string => $url('admin/terms?status=' . urlencode($targetStatus) . '&per_page=' . $listPerPage . '&page=1'),
        'searchPlaceholder' => $t('terms.search_placeholder'),
        'searchHidden' => ['status' => $statusCurrent, 'per_page' => (string)$listPerPage, 'page' => '1'],
        'query' => $listQuery,
    ],
    'table' => [
        'columns' => [
            ['label' => $t('common.name')],
            ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
        ],
        'rowRenderer' => $rowRenderer,
    ],
    'pagination' => [
        'page' => $listPage,
        'perPage' => $listPerPage,
        'totalPages' => $listTotalPages,
        'allowedPerPage' => $allowedPerPage,
        'perPageHidden' => ['status' => $statusCurrent, 'q' => $listQuery, 'page' => '1'],
        'url' => static fn(int $targetPage): string => $url('admin/terms?page=' . $targetPage . '&per_page=' . $listPerPage . '&status=' . urlencode($statusCurrent) . '&q=' . urlencode($listQuery)),
    ],
    'actions' => [
        'deleteConfirmText' => $t('terms.delete_confirm'),
    ],
];

require __DIR__ . '/../partials/list-layout.php';
