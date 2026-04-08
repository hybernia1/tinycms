<?php
$items = is_array($listItems ?? null) ? $listItems : [];
$listName = (string)($listName ?? 'list');
$listEndpoint = (string)($listEndpoint ?? '');
$listEditBase = (string)($listEditBase ?? '');
$listRootAttrs = is_array($listRootAttrs ?? null) ? $listRootAttrs : [];
$searchPlaceholder = (string)($searchPlaceholder ?? '');
$searchHidden = is_array($searchHidden ?? null) ? $searchHidden : [];
$perPageHidden = is_array($perPageHidden ?? null) ? $perPageHidden : [];
$listColumns = is_array($listColumns ?? null) ? $listColumns : [];
$listAllowedPerPage = is_array($listAllowedPerPage ?? null) ? $listAllowedPerPage : [];
$listPage = (int)($listPage ?? 1);
$listPerPage = (int)($listPerPage ?? \App\Service\Support\PaginationConfig::perPage());
$listTotalPages = (int)($listTotalPages ?? 1);
$listQuery = (string)($listQuery ?? '');
$statusEnabled = (bool)($statusEnabled ?? false);
$statusLinks = is_array($statusLinks ?? null) ? $statusLinks : [];
$statusCurrent = (string)($statusCurrent ?? 'all');
$statusUrl = is_callable($statusUrl ?? null) ? $statusUrl : null;
$paginationUrl = is_callable($paginationUrl ?? null) ? $paginationUrl : null;
$rowRenderer = is_callable($rowRenderer ?? null) ? $rowRenderer : null;
$deleteConfirmText = (string)($deleteConfirmText ?? '');
$csrfMarkup = (string)($csrfMarkup ?? '');

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
                    <a class="pagination-link<?= $listPage <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl !== null ? (string)$paginationUrl($prevPage) : '#', ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-prev<?= $listPage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                    <a class="pagination-link<?= $listPage >= $listTotalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl !== null ? (string)$paginationUrl($nextPage) : '#', ENT_QUOTES, 'UTF-8') ?>" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-next<?= $listPage >= $listTotalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
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
                <button class="btn btn-light" type="submit"><?= htmlspecialchars($t('common.apply', 'Apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>

    <?php
    $modal = [
        'id' => $listName . '-delete-modal',
        'attributes' => ['data-modal' => null, 'data-' . $listName . '-delete-modal' => null],
        'message' => $deleteConfirmText,
        'cancel_attributes' => ['type' => 'button', 'data-modal-close' => null, 'data-' . $listName . '-delete-cancel' => null],
        'confirm_attributes' => ['type' => 'button', 'data-' . $listName . '-delete-confirm' => null],
    ];
    require __DIR__ . '/modals/confirm.php';
    ?>
</div>
