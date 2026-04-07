<?php
$listItems = $pagination['data'] ?? [];
$listPage = (int)($pagination['page'] ?? 1);
$listPerPage = (int)($pagination['per_page'] ?? APP_POSTS_PER_PAGE);
$listTotalPages = (int)($pagination['total_pages'] ?? 1);
$listQuery = (string)($query ?? '');
$csrfMarkup = $csrfField();
$listName = 'terms';
$listEndpoint = $url('admin/api/v1/terms');
$listEditBase = $url('admin/terms/edit?id=');
$searchPlaceholder = $t('terms.search_placeholder', 'Search tag');
$searchHidden = ['per_page' => (string)$listPerPage, 'page' => '1'];
$perPageHidden = ['q' => $listQuery, 'page' => '1'];
$listColumns = [
    ['label' => $t('common.name', 'Name')],
    ['label' => $t('common.description', 'Description'), 'class' => 'table-col-mobile-hide'],
    ['label' => $t('common.actions', 'Actions'), 'class' => 'table-col-actions'],
];
$listAllowedPerPage = $allowedPerPage;
$statusEnabled = false;
$deleteConfirmText = $t('terms.delete_confirm', 'Do you really want to delete this tag?');
$paginationUrl = static fn(int $targetPage): string => $url('admin/terms?page=' . $targetPage . '&per_page=' . $listPerPage . '&q=' . urlencode($listQuery));
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t): string {
    $id = (int)($row['id'] ?? 0);
    ob_start();
    ?>
    <tr>
        <td>
            <a href="<?= htmlspecialchars($url('admin/terms/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
            <div class="text-muted small"><?= htmlspecialchars($formatDateTime((string)($row['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="table-col-mobile-hide"><?= htmlspecialchars((string)($row['body'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('terms.delete', 'Delete tag'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('terms.delete', 'Delete tag'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= htmlspecialchars($t('terms.delete', 'Delete tag'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};

require __DIR__ . '/../partials/list-layout.php';
