<?php
$users = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$status = (string)($status ?? 'all');
$query = (string)($query ?? '');
$statusLinks = [
    'all' => $t('users.status.all', 'All'),
    'active' => $t('users.status.active', 'Active'),
    'suspended' => $t('users.status.suspended', 'Suspended'),
];
$csrfMarkup = $csrfField();
?>
<div data-users-list data-endpoint="<?= htmlspecialchars($url('admin/api/v1/users'), ENT_QUOTES, 'UTF-8') ?>" data-edit-base="<?= htmlspecialchars($url('admin/users/edit?id='), ENT_QUOTES, 'UTF-8') ?>">
    <div data-users-csrf class="d-none"><?= $csrfMarkup ?></div>
<div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
    <nav class="filter-nav">
        <?php foreach ($statusLinks as $key => $label): ?>
            <a class="filter-link<?= $status === $key ? ' active' : '' ?>" data-users-status="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($url('admin/users?status=' . $key . '&per_page=' . $perPage . '&page=1'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="search-form">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <input type="hidden" name="page" value="1">
        <div class="search-field">
            <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('users.search_placeholder', 'Search name or email'), ENT_QUOTES, 'UTF-8') ?>" data-users-search>
            <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
        </div>
    </form>
</div>

<div class="card p-2">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars($t('users.user', 'User'), ENT_QUOTES, 'UTF-8') ?></th><th class="table-col-actions"><?= htmlspecialchars($t('common.actions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
            </thead>
            <tbody data-users-list-body>
            <?php foreach ($users as $row):
                $id = (int)($row['ID'] ?? 0);
                $isAdmin = (string)($row['role'] ?? '') === 'admin';
                $isSuspended = (int)($row['suspend'] ?? 0) === 1;
            ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars($url('admin/users/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        <div class="text-muted"><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="d-flex gap-2 mt-2">
                            <?php $roleValue = (string)($row['role'] ?? ''); ?>
                            <span class="badge text-bg-primary"><?= htmlspecialchars($t('users.roles.' . $roleValue, $roleValue), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($isSuspended): ?><span class="badge text-bg-warning"><?= htmlspecialchars($t('users.status.suspended_single', 'Suspended'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        </div>
                    </td>
                    <td class="table-col-actions">
                        <?php if (!$isAdmin): ?>
                        <form method="post" action="<?= htmlspecialchars($url('admin/api/v1/users/' . $id . '/suspend'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                            <?= $csrfField() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="mode" value="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>">
                            <button class="btn btn-light btn-icon" type="button" data-users-toggle="<?= $id ?>" data-users-mode="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>" aria-label="<?= htmlspecialchars($isSuspended ? $t('users.unsuspend', 'Unsuspend') : $t('users.suspend', 'Suspend'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($isSuspended ? $t('users.unsuspend', 'Unsuspend') : $t('users.suspend', 'Suspend'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon($isSuspended ? 'show' : 'hide') ?>
                                <span class="sr-only"><?= htmlspecialchars($isSuspended ? $t('users.unsuspend', 'Unsuspend') : $t('users.suspend', 'Suspend'), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </form>
                            <button class="btn btn-light btn-icon" type="button" data-users-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('users.delete', 'Delete user'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('users.delete', 'Delete user'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon('delete') ?>
                                <span class="sr-only"><?= htmlspecialchars($t('users.delete', 'Delete user'), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-between align-center mt-4">
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php $prevPage = max(1, $page - 1); ?>
            <?php $nextPage = min($totalPages, $page + 1); ?>
            <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" data-users-prev href="<?= htmlspecialchars($url('admin/users?page=' . $prevPage . '&per_page=' . $perPage . '&status=' . $status . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>
                <?= $icon('prev') ?>
                <span><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" data-users-next href="<?= htmlspecialchars($url('admin/users?page=' . $nextPage . '&per_page=' . $perPage . '&status=' . $status . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>>
                <span><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></span>
                <?= $icon('next') ?>
            </a>
        </div>
        <?php else: ?>
        <div></div>
        <?php endif; ?>

        <form method="get" class="d-flex gap-2 align-center">
            <select name="per_page" data-users-per-page>
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

<div class="modal-overlay" data-users-delete-modal>
    <div class="modal">
        <p><?= htmlspecialchars($t('users.delete_confirm', 'Do you really want to delete this user?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-users-delete-cancel><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-users-delete-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
</div>
