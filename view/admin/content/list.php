<?php
$items = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$status = (string)($status ?? 'all');
$query = (string)($query ?? '');
$type = (string)($contentType['type'] ?? 'post');
$statusLinks = ['all' => 'Vše'];
foreach ($availableStatuses as $statusValue) {
    $statusLinks[$statusValue] = ucfirst($statusValue);
}
?>
<div class="d-flex gap-2 align-center mb-3">
    <select name="action" id="bulk-action-select" disabled>
        <option value="">Hromadné akce</option>
        <option value="delete">Smazat</option>
    </select>
    <button class="btn btn-light" id="bulk-apply" type="button" disabled>Použít</button>
</div>

<div class="d-flex justify-between align-center mb-3">
    <nav class="filter-nav">
        <?php foreach ($statusLinks as $key => $label): ?>
            <a class="filter-link<?= $status === $key ? ' active' : '' ?>" href="<?= htmlspecialchars($url('admin/content?type=' . urlencode($type) . '&status=' . urlencode($key) . '&per_page=' . $perPage . '&page=1'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="d-flex gap-2 align-center">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <input type="hidden" name="page" value="1">
        <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Hledat název nebo obsah">
        <button class="btn btn-light btn-icon" type="submit" aria-label="Hledat" title="Hledat">
            <?= $icon('search') ?>
            <span class="sr-only">Hledat</span>
        </button>
    </form>
</div>

<form id="bulk-action-form" method="post" action="<?= htmlspecialchars($url('admin/content/bulk-action'), ENT_QUOTES, 'UTF-8') ?>" data-bulk-type="záznamů">
    <?= $csrfField() ?>
    <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="ids" value="">
    <input type="hidden" name="action" id="bulk-action-value" value="">
</form>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th class="table-col-select"><input type="checkbox" data-bulk-toggle></th>
                <th>Název</th><th>Status</th><th class="table-col-actions">Akce</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): $id = (int)($row['id'] ?? 0); ?>
                <tr>
                    <td class="table-col-select"><input type="checkbox" value="<?= $id ?>" data-bulk-item></td>
                    <td>
                        <a href="<?= htmlspecialchars($url('admin/content/edit?id=' . $id . '&type=' . urlencode($type)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        <div class="text-muted"><?= htmlspecialchars((string)($row['updated'] ?? $row['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td><span class="badge"><?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="table-col-actions">
                        <form id="delete-content-<?= $id ?>" method="post" action="<?= htmlspecialchars($url('admin/content/delete'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                            <?= $csrfField() ?>
                            <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-light btn-icon" type="button" data-modal-open data-modal-mode="single" data-type="obsah" data-form-id="delete-content-<?= $id ?>" aria-label="Smazat" title="Smazat">
                                <?= $icon('delete') ?>
                                <span class="sr-only">Smazat</span>
                            </button>
                        </form>
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
                <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/content?page=' . $prevPage . '&per_page=' . $perPage . '&type=' . urlencode($type) . '&status=' . urlencode($status) . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span>Předchozí</span></a>
                <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/content?page=' . $nextPage . '&per_page=' . $perPage . '&type=' . urlencode($type) . '&status=' . urlencode($status) . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span>Další</span><?= $icon('next') ?></a>
            </div>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <form method="get" class="d-flex gap-2 align-center">
            <select name="per_page">
                <?php foreach ($allowedPerPage as $option): ?>
                    <option value="<?= (int)$option ?>" <?= $perPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="page" value="1">
            <button class="btn btn-light" type="submit">Použít</button>
        </form>
    </div>
</div>

<div class="modal-overlay" data-modal>
    <div class="modal">
        <p data-modal-text>Skutečně smazat?</p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close>Zrušit</button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="">Potvrdit</button>
        </div>
    </div>
</div>
