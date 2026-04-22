<?php
if (!defined('BASE_DIR')) {
    exit;
}

$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = [
    'all' => $t('users.status.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
    'active' => $t('users.status.active') . ' (' . (int)($statusCounts['active'] ?? 0) . ')',
    'suspended' => $t('users.status.suspended') . ' (' . (int)($statusCounts['suspended'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $icon, $t, $csrfField, $escHtml): string {
    $id = (int)($row['ID'] ?? 0);
    $isAdmin = (string)($row['role'] ?? '') === 'admin';
    $isSuspended = (int)($row['suspend'] ?? 0) === 1;
    $statusIcon = $isSuspended ? 'suspended' : ($isAdmin ? 'admin' : 'users');
    ob_start();
    ?>
    <tr>
        <td>
            <span class="d-flex align-center gap-2">
                <?= $icon($statusIcon) ?>
                <a href="<?= $escUrl($url('admin/users/edit?id=' . $id)) ?>"><?= $escHtml((string)($row['name'] ?? '')) ?></a>
            </span>
            <div class="text-muted small"><?= $escHtml((string)($row['email'] ?? '')) ?></div>
        </td>
        <td class="table-col-actions">
            <?php if (!$isAdmin): ?>
                <form method="post" action="<?= $escUrl($url('admin/api/v1/users/' . $id . '/suspend')) ?>" class="inline-form">
                    <?= $csrfField() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="mode" value="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>">
                    <button class="btn btn-light btn-icon" type="button" data-users-toggle="<?= $id ?>" data-users-mode="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>" aria-label="<?= $escHtml($isSuspended ? $t('users.unsuspend') : $t('users.suspend')) ?>" title="<?= $escHtml($isSuspended ? $t('users.unsuspend') : $t('users.suspend')) ?>">
                        <?= $icon($isSuspended ? 'show' : 'hide') ?>
                        <span class="sr-only"><?= $escHtml($isSuspended ? $t('users.unsuspend') : $t('users.suspend')) ?></span>
                    </button>
                </form>
                <button class="btn btn-light btn-icon" type="button" data-users-delete-open="<?= $id ?>" aria-label="<?= $escHtml($t('users.delete')) ?>" title="<?= $escHtml($t('users.delete')) ?>">
                    <?= $icon('delete') ?>
                    <span class="sr-only"><?= $escHtml($t('users.delete')) ?></span>
                </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = $t('users.search_placeholder');
$list['columns'] = [
    ['label' => $t('users.user')],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = $t('users.delete_confirm');
$list['rowRenderer'] = $rowRenderer;

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
