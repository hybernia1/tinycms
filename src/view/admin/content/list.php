<?php
use App\Service\Support\AdminUrl;

$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = ['all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')'];
foreach ($availableStatuses as $statusValue) {
    $statusLinks[$statusValue] = $t('content.statuses.' . $statusValue, ucfirst($statusValue)) . ' (' . (int)($statusCounts[$statusValue] ?? 0) . ')';
}
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t, $csrfField, $e): string {
    $id = (int)($row['id'] ?? 0);
    $createdAtRaw = (string)($row['created'] ?? '');
    $createdAt = $formatDateTime($createdAtRaw);
    $createdStamp = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;
    $statusValue = (string)($row['status'] ?? '');
    $isPublished = $statusValue === 'published';
    $isPlanned = $isPublished && $createdStamp !== false && $createdStamp > time();
    $isTrash = $statusValue === 'trash';
    ob_start();
    ?>
    <tr>
        <td>
            <?php $statusIcon = $isPlanned ? 'calendar' : ($statusValue === 'published' ? 'success' : ($statusValue === 'draft' ? 'concept' : 'warning')); ?>
            <span class="d-flex align-center gap-2">
                <?php if ($statusIcon !== ''): ?><?= $icon($statusIcon) ?><?php endif; ?>
                <a href="<?= $e($url(AdminUrl::entityEdit('content', $id))) ?>"><?= $e((string)($row['name'] ?? '')) ?></a>
            </span>
            <div class="text-muted small"><?= $e($createdAt) ?></div>
        </td>
        <td class="mobile-hide"><?= $e((string)($row['author_name'] ?? '—')) ?></td>
        <td class="table-col-actions">
            <?php if (!$isTrash): ?>
                <form method="post" action="<?= $e($url('admin/api/v1/content/' . $id . '/status')) ?>" class="inline-form">
                    <?= $csrfField() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="mode" value="<?= $isPublished ? 'draft' : 'publish' ?>">
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="<?= $id ?>" data-content-mode="<?= $isPublished ? 'draft' : 'publish' ?>" aria-label="<?= $e($isPublished ? $t('content.switch_to_draft') : $t('content.publish')) ?>" title="<?= $e($isPublished ? $t('content.switch_to_draft') : $t('content.publish')) ?>">
                        <?= $icon($isPublished ? 'hide' : 'show') ?>
                        <span class="sr-only"><?= $e($isPublished ? $t('content.switch_to_draft') : $t('content.publish')) ?></span>
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-light btn-icon" type="button" data-content-restore="<?= $id ?>" aria-label="<?= $e($t('content.restore')) ?>" title="<?= $e($t('content.restore')) ?>">
                    <?= $icon('restore') ?>
                    <span class="sr-only"><?= $e($t('content.restore')) ?></span>
                </button>
            <?php endif; ?>
            <button class="btn btn-light btn-icon" type="button" data-content-delete-open="<?= $id ?>" data-content-delete-mode="<?= $isTrash ? 'hard' : 'soft' ?>" aria-label="<?= $e($t('common.delete')) ?>" title="<?= $e($t('common.delete')) ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= $e($t('common.delete')) ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = $t('content.search_placeholder');
$list['columns'] = [
    ['label' => $t('common.name')],
    ['label' => $t('common.author'), 'class' => 'mobile-hide'],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = $t('content.delete_confirm_move_to_trash');
$list['rowRenderer'] = $rowRenderer;

require __DIR__ . '/../partials/list-layout.php';
