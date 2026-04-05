<?php
$items = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$query = (string)($query ?? '');
$csrfMarkup = $csrfField();
?>
<div data-terms-list data-endpoint="<?= htmlspecialchars($url('admin/api/v1/terms'), ENT_QUOTES, 'UTF-8') ?>" data-edit-base="<?= htmlspecialchars($url('admin/terms/edit?id='), ENT_QUOTES, 'UTF-8') ?>">
    <div data-terms-csrf class="d-none"><?= $csrfMarkup ?></div>
    <div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
        <div></div>
        <form method="get" class="search-form">
            <input type="hidden" name="per_page" value="<?= $perPage ?>">
            <input type="hidden" name="page" value="1">
            <div class="search-field">
                <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('terms.search_placeholder', 'Search tag'), ENT_QUOTES, 'UTF-8') ?>" data-terms-search>
                <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
            </div>
        </form>
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('common.description', 'Description'), ENT_QUOTES, 'UTF-8') ?></th><th class="table-col-actions"><?= htmlspecialchars($t('common.actions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody data-terms-list-body>
                <?php foreach ($items as $row): ?>
                    <?php $id = (int)($row['id'] ?? 0); ?>
                    <tr>
                        <td>
                            <a href="<?= htmlspecialchars($url('admin/terms/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                            <div class="text-muted"><?= htmlspecialchars($formatDateTime((string)($row['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= htmlspecialchars((string)($row['body'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="table-col-actions">
                            <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('terms.delete', 'Delete tag'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('terms.delete', 'Delete tag'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon('delete') ?>
                                <span class="sr-only"><?= htmlspecialchars($t('terms.delete', 'Delete tag'), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-between align-center mt-4">
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php $prevPage = max(1, $page - 1); $nextPage = min($totalPages, $page + 1); ?>
                    <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/terms?page=' . $prevPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>" data-terms-prev<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                    <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/terms?page=' . $nextPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>" data-terms-next<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
                </div>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <form method="get" class="d-flex gap-2 align-center">
                <select name="per_page" data-terms-per-page>
                    <?php foreach ($allowedPerPage as $option): ?>
                        <option value="<?= (int)$option ?>" <?= $perPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="page" value="1">
                <button class="btn btn-light" type="submit"><?= htmlspecialchars($t('common.apply', 'Apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" data-terms-delete-modal>
        <div class="modal">
            <p><?= htmlspecialchars($t('terms.delete_confirm', 'Do you really want to delete this tag?'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-terms-delete-cancel><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button class="btn btn-primary" type="button" data-terms-delete-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
