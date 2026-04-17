<?php
if (!defined('BASE_DIR')) {
    exit;
}

$list = is_array($list ?? null) ? $list : [];
$items = is_array($list['items'] ?? null) ? $list['items'] : [];
$entity = (string)($list['entity'] ?? '');
$listName = (string)($list['name'] ?? $entity);
$statusCurrent = (string)($list['statusCurrent'] ?? 'all');
$listQuery = (string)($list['query'] ?? '');
$listEndpoint = (string)($list['endpoint'] ?? ($entity !== '' ? $url('admin/api/v1/' . $entity) : ''));
$listEditBase = (string)($list['editBase'] ?? ($entity !== '' ? $url('admin/' . $entity . '/edit?id=') : ''));
$listRootAttrs = is_array($list['rootAttrs'] ?? null) ? $list['rootAttrs'] : [];
$searchPlaceholder = (string)($list['searchPlaceholder'] ?? '');
$listColumns = is_array($list['columns'] ?? null) ? $list['columns'] : [];
$listPage = (int)($list['page'] ?? 1);
$listTotalPages = (int)($list['totalPages'] ?? 1);
$statusLinks = is_array($list['statusLinks'] ?? null) ? $list['statusLinks'] : [];
$statusEnabled = (bool)($list['statusEnabled'] ?? $statusLinks !== []);
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
    <?= $e((string)$attr) ?><?= $value === null ? '' : '="' . $e((string)$value) . '"' ?>
<?php endforeach; ?>
>
    <div data-<?= $e($listName) ?>-csrf class="d-none"><?= $csrfMarkup ?></div>
    <div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
        <?php if ($statusEnabled): ?>
            <nav class="filter-nav">
                <?php foreach ($statusLinks as $key => $label): ?>
                    <button class="filter-link<?= $statusCurrent === (string)$key ? ' active' : '' ?>" type="button" data-<?= $e($listName) ?>-status="<?= $e((string)$key) ?>"><?= $e((string)$label) ?></button>
                <?php endforeach; ?>
            </nav>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
        <div class="search-form">
            <div class="search-field field-with-icon">
                <input class="search-input" type="search" value="<?= $e($listQuery) ?>" placeholder="<?= $e($searchPlaceholder) ?>" data-<?= $e($listName) ?>-search>
                <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= $icon('search') ?></span>
            </div>
        </div>
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
                        <th<?= $class !== '' ? ' class="' . $e($class) . '"' : '' ?>><?= $e($label) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody data-<?= $e($listName) ?>-list-body>
                <?php foreach ($items as $row): ?>
                    <?= $rowRenderer !== null ? $rowRenderer((array)$row) : '' ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-between align-center mt-4">
            <?php if ($listTotalPages > 1): ?>
                <div class="pagination">
                    <button class="pagination-link<?= $listPage <= 1 ? ' disabled' : '' ?>" type="button" data-<?= $e($listName) ?>-prev<?= $listPage <= 1 ? ' disabled aria-disabled="true"' : '' ?>><?= $icon('prev') ?><span><?= $e($t('common.previous')) ?></span></button>
                    <button class="pagination-link<?= $listPage >= $listTotalPages ? ' disabled' : '' ?>" type="button" data-<?= $e($listName) ?>-next<?= $listPage >= $listTotalPages ? ' disabled aria-disabled="true"' : '' ?>><span><?= $e($t('common.next')) ?></span><?= $icon('next') ?></button>
                </div>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <div></div>
        </div>
    </div>

    <div class="modal-overlay" data-modal data-<?= $e($listName) ?>-delete-modal>
        <div class="modal">
            <p data-modal-text><?= $e($deleteConfirmText) ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-modal-close><?= $e($t('common.cancel')) ?></button>
                <button class="btn btn-primary" type="button" data-modal-confirm><?= $e($t('common.confirm')) ?></button>
            </div>
        </div>
    </div>
</div>
