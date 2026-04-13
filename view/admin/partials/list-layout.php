<?php
$list = is_array($list ?? null) ? $list : [];
$items = is_array($list['items'] ?? null) ? $list['items'] : [];
$entity = (string)($list['entity'] ?? '');
$listName = (string)($list['name'] ?? $entity);
$listPerPage = (int)($list['perPage'] ?? \App\Service\Support\PaginationConfig::perPage());
$statusCurrent = (string)($list['statusCurrent'] ?? 'all');
$listQuery = (string)($list['query'] ?? '');
$listEndpoint = (string)($list['endpoint'] ?? ($entity !== '' ? $url('admin/api/v1/' . $entity) : ''));
$listEditBase = (string)($list['editBase'] ?? ($entity !== '' ? $url('admin/' . $entity . '/edit?id=') : ''));
$listRootAttrs = is_array($list['rootAttrs'] ?? null) ? $list['rootAttrs'] : [];
$searchPlaceholder = (string)($list['searchPlaceholder'] ?? '');
$searchHidden = is_array($list['searchHidden'] ?? null) ? $list['searchHidden'] : ['status' => $statusCurrent, 'per_page' => (string)$listPerPage, 'page' => '1'];
$perPageHidden = is_array($list['perPageHidden'] ?? null) ? $list['perPageHidden'] : ['status' => $statusCurrent, 'q' => $listQuery, 'page' => '1'];
$listColumns = is_array($list['columns'] ?? null) ? $list['columns'] : [];
$listAllowedPerPage = is_array($list['allowedPerPage'] ?? null) ? $list['allowedPerPage'] : \App\Service\Support\PaginationConfig::allowed();
$listPage = (int)($list['page'] ?? 1);
$listTotalPages = (int)($list['totalPages'] ?? 1);
$statusLinks = is_array($list['statusLinks'] ?? null) ? $list['statusLinks'] : [];
$statusEnabled = (bool)($list['statusEnabled'] ?? $statusLinks !== []);
$statusUrl = is_callable($list['statusUrl'] ?? null)
    ? $list['statusUrl']
    : static fn(string $targetStatus): string => $url('admin/' . $entity . '?status=' . urlencode($targetStatus) . '&per_page=' . $listPerPage . '&page=1');
$paginationUrl = is_callable($list['paginationUrl'] ?? null)
    ? $list['paginationUrl']
    : static fn(int $targetPage): string => $url('admin/' . $entity . '?page=' . $targetPage . '&per_page=' . $listPerPage . '&status=' . urlencode($statusCurrent) . '&q=' . urlencode($listQuery));
$rowRenderer = is_callable($list['rowRenderer'] ?? null) ? $list['rowRenderer'] : null;
$deleteConfirmText = (string)($list['deleteConfirmText'] ?? '');
$csrfMarkup = (string)($list['csrfMarkup'] ?? $csrfField());

$rootAttrs = [
    'data-' . $listName . '-list' => null,
    'data-endpoint' => $listEndpoint,
    'data-edit-base' => $listEditBase,
];
foreach ($listRootAttrs as $attr => $value) {
    $rootAttrs[(string)$attr] = $value;
}
?>
<div
<?php foreach ($rootAttrs as $attr => $value): ?>
    <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
<?php endforeach; ?>
>
    <div data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-csrf class="d-none"><?= $csrfMarkup ?></div>
    <div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
        <?php if ($statusEnabled): ?>
            <nav class="filter-nav">
                <?php foreach ($statusLinks as $key => $label): ?>
                    <a class="filter-link<?= $statusCurrent === (string)$key ? ' active' : '' ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-status="<?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($statusUrl !== null ? (string)$statusUrl((string)$key) : '#', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </nav>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
        <form method="get" class="search-form">
            <?php foreach ($searchHidden as $name => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <div class="search-field field-with-icon">
                <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($listQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-search>
                <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= $icon('search') ?></span>
            </div>
        </form>
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <?php foreach ($listColumns as $column): ?>
                        <?php
                        $label = (string)($column['label'] ?? '');
                        $class = trim((string)($column['class'] ?? ''));
                        ?>
                        <th<?= $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-list-body>
                <?php foreach ($items as $row): ?>
                    <?= $rowRenderer !== null ? $rowRenderer((array)$row) : '' ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-between align-center mt-4">
            <?php if ($listTotalPages > 1): ?>
                <div class="pagination">
                    <?php $prevPage = max(1, $listPage - 1); $nextPage = min($listTotalPages, $listPage + 1); ?>
                    <a class="pagination-link<?= $listPage <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl !== null ? (string)$paginationUrl($prevPage) : '#', ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-prev<?= $listPage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                    <a class="pagination-link<?= $listPage >= $listTotalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl !== null ? (string)$paginationUrl($nextPage) : '#', ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-next<?= $listPage >= $listTotalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
                </div>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <form method="get" class="d-flex gap-2 align-center">
                <select name="per_page" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-per-page>
                    <?php foreach ($listAllowedPerPage as $option): ?>
                        <option value="<?= (int)$option ?>" <?= $listPerPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                    <?php endforeach; ?>
                </select>
                <?php foreach ($perPageHidden as $name => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>">
                <?php endforeach; ?>
            </form>
        </div>
    </div>

    <div class="modal-overlay" data-modal data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-modal>
        <div class="modal">
            <p><?= htmlspecialchars($deleteConfirmText, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-cancel><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button class="btn btn-primary" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-confirm><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
