<?php
if (!defined('BASE_DIR')) {
    exit;
}

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
    $source = trim((string)($row['source'] ?? ''));
    ob_start();
    ?>
    <tr>
        <td>
            <?php $statusIcon = $isPlanned ? 'calendar' : ($statusValue === 'published' ? 'success' : ($statusValue === 'draft' ? 'concept' : 'warning')); ?>
            <span class="d-flex align-center gap-2">
                <?php if ($statusIcon !== ''): ?><?= $icon($statusIcon) ?><?php endif; ?>
                <a href="<?= $e($url('admin/content/edit?id=' . $id)) ?>"><?= $e((string)($row['name'] ?? '')) ?></a>
            </span>
            <div class="text-muted small"><?= $e($createdAt) ?></div>
        </td>
        <td class="mobile-hide"><?= $e((string)($row['author_name'] ?? '—')) ?></td>
        <td class="mobile-hide">
            <?php if ($source !== ''): ?>
                <a href="<?= $e($source) ?>" target="_blank" rel="noreferrer noopener"><?= $e($source) ?></a>
            <?php else: ?>
                —
            <?php endif; ?>
        </td>
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
    ['label' => $t('content.source'), 'class' => 'mobile-hide'],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = $t('content.delete_confirm_move_to_trash');
$list['rowRenderer'] = $rowRenderer;
?>

<form method="post" action="<?= $e($url('admin/api/v1/content/import/wp')) ?>" data-api-submit class="card p-3 mb-3">
    <?= $csrfField() ?>
    <div class="d-flex gap-2 align-end wrap">
        <div class="field" style="flex:1 1 320px;">
            <label for="wp-import-site"><?= $e($t('content.import_site_url')) ?></label>
            <input id="wp-import-site" type="url" name="site_url" placeholder="https://example.com" required>
        </div>
        <div class="field" style="width:120px;">
            <label for="wp-import-start"><?= $e($t('content.import_start_page')) ?></label>
            <input id="wp-import-start" type="number" name="start_page" min="1" value="1" required>
        </div>
        <div class="field" style="width:120px;">
            <label for="wp-import-batch"><?= $e($t('content.import_batch_pages')) ?></label>
            <input id="wp-import-batch" type="number" name="batch_pages" min="1" max="10" value="2" required>
        </div>
        <button class="btn btn-primary" type="submit"><?= $e($t('content.import_wp_submit')) ?></button>
    </div>
    <small class="text-muted"><?= $e($t('content.import_wp_help')) ?></small>
    <div class="text-muted small mt-2" data-api-form-message hidden></div>
</form>

<?php require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php'; ?>
