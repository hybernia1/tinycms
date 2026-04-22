<?php
if (!defined('BASE_DIR')) {
    exit;
}

$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = ['all' => t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')'];
foreach ($availableStatuses as $statusValue) {
    $statusLinks[$statusValue] = t('content.statuses.' . $statusValue, ucfirst($statusValue)) . ' (' . (int)($statusCounts[$statusValue] ?? 0) . ')';
}
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $csrfField): string {
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
                <?php if ($statusIcon !== ''): ?><?= icon($statusIcon) ?><?php endif; ?>
                <a href="<?= esc_url($url('admin/content/edit?id=' . $id)) ?>"><?= esc_html((string)($row['name'] ?? '')) ?></a>
            </span>
            <div class="text-muted small"><?= esc_html($createdAt) ?></div>
        </td>
        <td class="mobile-hide"><?= esc_html((string)($row['author_name'] ?? '—')) ?></td>
        <td class="table-col-actions">
            <?php if (!$isTrash): ?>
                <form method="post" action="<?= esc_url($url('admin/api/v1/content/' . $id . '/status')) ?>" class="inline-form">
                    <?= $csrfField() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="mode" value="<?= $isPublished ? 'draft' : 'publish' ?>">
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="<?= $id ?>" data-content-mode="<?= $isPublished ? 'draft' : 'publish' ?>" aria-label="<?= esc_attr($isPublished ? t('content.switch_to_draft') : t('content.publish')) ?>" title="<?= esc_attr($isPublished ? t('content.switch_to_draft') : t('content.publish')) ?>">
                        <?= icon($isPublished ? 'hide' : 'show') ?>
                        <span class="sr-only"><?= esc_html($isPublished ? t('content.switch_to_draft') : t('content.publish')) ?></span>
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-light btn-icon" type="button" data-content-restore="<?= $id ?>" aria-label="<?= esc_attr(t('content.restore')) ?>" title="<?= esc_attr(t('content.restore')) ?>">
                    <?= icon('restore') ?>
                    <span class="sr-only"><?= esc_html(t('content.restore')) ?></span>
                </button>
            <?php endif; ?>
            <button class="btn btn-light btn-icon" type="button" data-content-delete-open="<?= $id ?>" data-content-delete-mode="<?= $isTrash ? 'hard' : 'soft' ?>" aria-label="<?= esc_attr(t('common.delete')) ?>" title="<?= esc_attr(t('common.delete')) ?>">
                <?= icon('delete') ?>
                <span class="sr-only"><?= esc_html(t('common.delete')) ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = t('content.search_placeholder');
$list['columns'] = [
    ['label' => t('common.name')],
    ['label' => t('common.author'), 'class' => 'mobile-hide'],
    ['label' => t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = t('content.delete_confirm_move_to_trash');
$list['rowRenderer'] = $rowRenderer;

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
