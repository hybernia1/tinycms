<?php
$items = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$status = (string)($status ?? 'all');
$query = (string)($query ?? '');
$statusLinks = ['all' => $t('common.all', 'All')];
foreach ($availableStatuses as $statusValue) {
    $statusLinks[$statusValue] = $t('content.statuses.' . $statusValue, ucfirst($statusValue));
}
$csrfMarkup = $csrfField();
?>
<div data-content-list data-endpoint="<?= htmlspecialchars($url('admin/api/v1/content'), ENT_QUOTES, 'UTF-8') ?>" data-edit-base="<?= htmlspecialchars($url('admin/content/edit?id='), ENT_QUOTES, 'UTF-8') ?>">
    <div data-content-csrf class="d-none"><?= $csrfMarkup ?></div>
<div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
    <nav class="filter-nav">
        <?php foreach ($statusLinks as $key => $label): ?>
            <a class="filter-link<?= $status === $key ? ' active' : '' ?>" data-content-status="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($url('admin/content?status=' . urlencode($key) . '&per_page=' . $perPage . '&page=1'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="search-form">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <input type="hidden" name="page" value="1">
        <div class="search-field">
            <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('content.search_placeholder', 'Search title or content'), ENT_QUOTES, 'UTF-8') ?>" data-content-search>
            <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
        </div>
    </form>
</div>

<div class="card p-2">
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('common.author', 'Author'), ENT_QUOTES, 'UTF-8') ?></th><th class="table-col-actions"><?= htmlspecialchars($t('common.actions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody data-content-list-body>
            <?php foreach ($items as $row):
                $id = (int)($row['id'] ?? 0);
                $createdAtRaw = (string)($row['created'] ?? '');
                $createdAt = $formatDateTime($createdAtRaw);
                $createdStamp = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;
                $isPlanned = $createdStamp !== false && $createdStamp > time();
                $statusValue = (string)($row['status'] ?? '');
                $statusClass = $statusValue === 'published' ? 'text-bg-success' : ($statusValue === 'draft' ? 'text-bg-dark' : 'text-bg-primary');
            ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars($url('admin/content/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        <div class="text-muted">
                            <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <span class="badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('content.statuses.' . $statusValue, $statusValue), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isPlanned): ?><span class="badge text-bg-warning"><?= htmlspecialchars($t('content.planned', 'Planned'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars((string)($row['author_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="table-col-actions">
                        <?php $isPublished = (string)($row['status'] ?? '') === 'published'; ?>
                        <form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $id . '/status'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                            <?= $csrfField() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="mode" value="<?= $isPublished ? 'draft' : 'publish' ?>">
                            <button class="btn btn-light btn-icon" type="button" data-content-toggle="<?= $id ?>" data-content-mode="<?= $isPublished ? 'draft' : 'publish' ?>" aria-label="<?= htmlspecialchars($isPublished ? $t('content.switch_to_draft', 'Switch to draft') : $t('content.publish', 'Publish'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($isPublished ? $t('content.switch_to_draft', 'Switch to draft') : $t('content.publish', 'Publish'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon($isPublished ? 'hide' : 'show') ?>
                                <span class="sr-only"><?= htmlspecialchars($isPublished ? $t('content.switch_to_draft', 'Switch to draft') : $t('content.publish', 'Publish'), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </form>
                        <button class="btn btn-light btn-icon" type="button" data-content-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon('delete') ?>
                                <span class="sr-only"><?= htmlspecialchars($t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?></span>
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
                <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/content?page=' . $prevPage . '&per_page=' . $perPage . '&status=' . urlencode($status) . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>" data-content-prev<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= htmlspecialchars($url('admin/content?page=' . $nextPage . '&per_page=' . $perPage . '&status=' . urlencode($status) . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>" data-content-next<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
            </div>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <form method="get" class="d-flex gap-2 align-center">
            <select name="per_page" data-content-per-page>
                <?php foreach ($allowedPerPage as $option): ?>
                    <option value="<?= (int)$option ?>" <?= $perPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="page" value="1">
            <button class="btn btn-light" type="submit"><?= htmlspecialchars($t('common.apply', 'Apply'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
</div>

<div class="modal-overlay" data-content-delete-modal>
    <div class="modal">
        <p><?= htmlspecialchars($t('content.delete_confirm', 'Do you really want to delete this content?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-content-delete-cancel><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-content-delete-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
</div>
