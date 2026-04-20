<?php
if (!defined('BASE_DIR')) {
    exit;
}

$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = [
    'all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')',
];
$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t, $e): string {
    $id = (int)($row['id'] ?? 0);
    $contentId = (int)($row['content'] ?? 0);
    $contentName = trim((string)($row['content_name'] ?? ''));
    $body = trim((string)($row['body'] ?? ''));
    if (mb_strlen($body) > 160) {
        $body = mb_substr($body, 0, 160) . '…';
    }

    ob_start();
    ?>
    <tr>
        <td>
            <a href="<?= $e($url('admin/comments/edit?id=' . $id)) ?>"><?= $e($t('comments.comment')) ?> #<?= $id ?></a>
            <br>
            <?php if ($contentId > 0 && $contentName !== ''): ?>
                <a href="<?= $e($url('admin/content/edit?id=' . $contentId)) ?>"><?= $e($contentName) ?></a>
            <?php else: ?>
                <span><?= $e($t('comments.no_content')) ?></span>
            <?php endif; ?>
            <div class="text-muted small"><?= $e((string)($row['author_name'] ?? '')) ?> · <?= $e($formatDateTime((string)($row['created'] ?? ''))) ?></div>
            <div class="small"><?= $e($body) ?></div>
        </td>
        <td class="table-col-actions">
            <button class="btn btn-light btn-icon" type="button" data-comments-delete-open="<?= $id ?>" aria-label="<?= $e($t('comments.delete')) ?>" title="<?= $e($t('comments.delete')) ?>">
                <?= $icon('delete') ?>
                <span class="sr-only"><?= $e($t('comments.delete')) ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};

$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = $t('comments.search_placeholder');
$list['columns'] = [
    ['label' => $t('comments.comment')],
    ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
];
$list['deleteConfirmText'] = $t('comments.delete_confirm');
$list['rowRenderer'] = $rowRenderer;

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
