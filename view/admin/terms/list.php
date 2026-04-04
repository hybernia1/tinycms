<?php
$items = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$query = (string)($query ?? '');
$csrfMarkup = $csrfField();
?>
<div data-terms-list data-endpoint="<?= htmlspecialchars($url('admin/terms'), ENT_QUOTES, 'UTF-8') ?>" data-edit-base="<?= htmlspecialchars($url('admin/terms/edit?id='), ENT_QUOTES, 'UTF-8') ?>">
    <div data-terms-csrf class="d-none"><?= $csrfMarkup ?></div>
    <div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
        <div></div>
        <form method="get" class="search-form">
            <input type="hidden" name="per_page" value="<?= $perPage ?>">
            <input type="hidden" name="page" value="1">
            <div class="search-field">
                <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Hledat štítek" data-terms-search>
                <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
            </div>
        </form>
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>Název</th><th>Popis</th><th class="table-col-actions">Akce</th>
                </tr>
                </thead>
                <tbody data-terms-list-body>
                <?php foreach ($items as $row): ?>
                    <?php $id = (int)($row['id'] ?? 0); ?>
                    <tr>
                        <td>
                            <a href="<?= htmlspecialchars($url('admin/terms/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                            <div class="text-muted"><?= htmlspecialchars((string)($row['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= htmlspecialchars((string)($row['body'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="table-col-actions">
                            <button class="btn btn-light btn-icon" type="button" data-terms-delete-open="<?= $id ?>" aria-label="Smazat štítek" title="Smazat štítek">
                                <?= $icon('delete') ?>
                                <span class="sr-only">Smazat štítek</span>
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
                    <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/terms?page=' . $prevPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>" data-terms-prev<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span>Předchozí</span></a>
                    <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/terms?page=' . $nextPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>" data-terms-next<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span>Další</span><?= $icon('next') ?></a>
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
                <button class="btn btn-light" type="submit">Použít</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" data-terms-delete-modal>
        <div class="modal">
            <p>Skutečně smazat tento štítek?</p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-terms-delete-cancel>Zrušit</button>
                <button class="btn btn-primary" type="button" data-terms-delete-confirm>Potvrdit</button>
            </div>
        </div>
    </div>
</div>
