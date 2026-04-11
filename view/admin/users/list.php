<?php
$listModel = ($list ?? null) instanceof \App\View\Admin\AdminListViewModel ? $list : null;
$listItems = $listModel?->items ?? (array)($pagination['data'] ?? []);
$listPage = $listModel?->page ?? (int)($pagination['page'] ?? 1);
$listPerPage = $listModel?->perPage ?? (int)($pagination['per_page'] ?? \App\Service\Support\PaginationConfig::perPage());
$listTotalPages = $listModel?->totalPages ?? (int)($pagination['total_pages'] ?? 1);
$statusCurrent = $listModel?->status ?? (string)($status ?? 'all');
$listQuery = $listModel?->query ?? (string)($query ?? '');
$statusCounts = $listModel?->statusCounts ?? (is_array($statusCounts ?? null) ? $statusCounts : []);
$statusLinks = [
    'all' => $t('users.status.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'active' => $t('users.status.active') . ' (' . (int)($statusCounts['active'] ?? 0) . ')',
    'suspended' => $t('users.status.suspended') . ' (' . (int)($statusCounts['suspended'] ?? 0) . ')',
];
$listName = 'users';
$listEndpoint = $url('admin/api/v1/users');
$listEditBase = $url('admin/users/edit?id=');
$searchPlaceholder = $t('users.search_placeholder');
$listColumns = [
    ['label' => $t('users.user')],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$listAllowedPerPage = $listModel?->allowedPerPage ?? ($allowedPerPage ?? []);
$statusEnabled = true;
$deleteConfirmText = $t('users.delete_confirm');
$statusUrl = static fn(string $targetStatus): string => $url('admin/users?status=' . $targetStatus . '&per_page=' . $listPerPage . '&page=1');
$paginationUrl = static fn(int $targetPage): string => $url('admin/users?page=' . $targetPage . '&per_page=' . $listPerPage . '&status=' . $statusCurrent . '&q=' . urlencode($listQuery));
$rowRenderer = static function (array $row) use ($url, $icon, $t, $csrfField): string {
    $id = (int)($row['ID'] ?? 0);
    $isAdmin = (string)($row['role'] ?? '') === 'admin';
    $isSuspended = (int)($row['suspend'] ?? 0) === 1;
    $roleValue = (string)($row['role'] ?? '');
    ob_start();
    ?>
    <tr>
        <td>
            <a href="<?= htmlspecialchars($url('admin/users/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
            <div class="text-muted small"><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="d-flex gap-2 mt-2">
                <span class="badge text-bg-primary"><?= htmlspecialchars($t('users.roles.' . $roleValue, $roleValue), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($isSuspended): ?><span class="badge text-bg-warning"><?= htmlspecialchars($t('users.status.suspended_single'), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
        </td>
        <td class="table-col-actions">
            <?php if (!$isAdmin): ?>
                <form method="post" action="<?= htmlspecialchars($url('admin/api/v1/users/' . $id . '/suspend'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                    <?= $csrfField() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="mode" value="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>">
                    <button class="btn btn-light btn-icon" type="button" data-users-toggle="<?= $id ?>" data-users-mode="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>" aria-label="<?= htmlspecialchars($isSuspended ? $t('users.unsuspend') : $t('users.suspend'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($isSuspended ? $t('users.unsuspend') : $t('users.suspend'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= $icon($isSuspended ? 'show' : 'hide') ?>
                        <span class="sr-only"><?= htmlspecialchars($isSuspended ? $t('users.unsuspend') : $t('users.suspend'), ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                </form>
                <button class="btn btn-light btn-icon" type="button" data-users-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('users.delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('users.delete'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('delete') ?>
                    <span class="sr-only"><?= htmlspecialchars($t('users.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};

require __DIR__ . '/../partials/list-layout.php';
