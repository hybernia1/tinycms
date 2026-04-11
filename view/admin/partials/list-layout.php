<?php
$config = is_array($listConfig ?? null) ? $listConfig : [];

$items = is_array($listItems ?? null) ? $listItems : [];
$meta = is_array($config['meta'] ?? null) ? $config['meta'] : [];
$filters = is_array($config['filters'] ?? null) ? $config['filters'] : [];
$table = is_array($config['table'] ?? null) ? $config['table'] : [];
$pagination = is_array($config['pagination'] ?? null) ? $config['pagination'] : [];
$actions = is_array($config['actions'] ?? null) ? $config['actions'] : [];

$listName = (string)($meta['name'] ?? 'list');
$listEndpoint = (string)($meta['endpoint'] ?? '');
$listEditBase = (string)($meta['editBase'] ?? '');
$listRootAttrs = is_array($meta['rootAttrs'] ?? null) ? $meta['rootAttrs'] : [];
$csrfMarkup = (string)($meta['csrfMarkup'] ?? '');

$statusEnabled = (bool)($filters['statusEnabled'] ?? false);
$statusLinks = is_array($filters['statusLinks'] ?? null) ? $filters['statusLinks'] : [];
$statusCurrent = (string)($filters['statusCurrent'] ?? 'all');
$statusUrl = is_callable($filters['statusUrl'] ?? null) ? $filters['statusUrl'] : null;
$searchPlaceholder = (string)($filters['searchPlaceholder'] ?? '');
$searchHidden = is_array($filters['searchHidden'] ?? null) ? $filters['searchHidden'] : [];
$listQuery = (string)($filters['query'] ?? '');

$listColumns = is_array($table['columns'] ?? null) ? $table['columns'] : [];
$rowRenderer = is_callable($table['rowRenderer'] ?? null) ? $table['rowRenderer'] : null;

$listPage = (int)($pagination['page'] ?? 1);
$listPerPage = (int)($pagination['perPage'] ?? \App\Service\Support\PaginationConfig::perPage());
$listTotalPages = (int)($pagination['totalPages'] ?? 1);
$listAllowedPerPage = is_array($pagination['allowedPerPage'] ?? null) ? $pagination['allowedPerPage'] : [];
$perPageHidden = is_array($pagination['perPageHidden'] ?? null) ? $pagination['perPageHidden'] : [];
$paginationUrl = is_callable($pagination['url'] ?? null) ? $pagination['url'] : null;

$deleteConfirmText = (string)($actions['deleteConfirmText'] ?? '');

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
                <button class="btn btn-light" type="submit"><?= htmlspecialchars($t('common.apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-modal>
        <div class="modal">
            <p><?= htmlspecialchars($deleteConfirmText, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-cancel><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button class="btn btn-primary" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-confirm><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
