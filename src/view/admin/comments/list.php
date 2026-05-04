<?php
if (!defined('BASE_DIR')) {
    exit;
}

$list = $listBase ?? [];
$statusCounts = (array)($list['statusCounts'] ?? []);
$statusLinks = ['all' => t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')'];
foreach (['published', 'draft', 'trash'] as $statusValue) {
    $statusLinks[$statusValue] = t('comments.statuses.' . $statusValue, ucfirst($statusValue)) . ' (' . (int)($statusCounts[$statusValue] ?? 0) . ')';
}
$rowRenderer = static function (array $row) use ($url, $formatDateTime): string {
    $id = (int)($row['id'] ?? 0);
    $contentId = (int)($row['content'] ?? 0);
    $parentId = (int)($row['parent'] ?? 0);
    $repliesCount = (int)($row['replies_count'] ?? 0);
    $statusValue = (string)($row['status'] ?? 'draft');
    $isPublished = $statusValue === 'published';
    $isTrash = $statusValue === 'trash';
    $statusIcon = $statusValue === 'published' ? 'success' : ($statusValue === 'draft' ? 'concept' : 'warning');
    $body = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)($row['body'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    $body = mb_strlen($body) > 160 ? mb_substr($body, 0, 157) . '...' : $body;
    $author = trim((string)($row['author_name'] ?? ''));
    if ($author === '') {
        $author = trim((string)($row['author_email'] ?? ''));
    }
    ob_start();
    ?>
    <tr>
        <td>
            <span class="d-flex align-center gap-2">
                <?= icon($statusIcon) ?>
                <?php if ($parentId > 0): ?>
                    <span class="admin-list-truncate" title="<?= esc_attr($body) ?>"><?= esc_html($body !== '' ? $body : t('comments.empty_body')) ?></span>
                <?php else: ?>
                    <a class="admin-list-truncate" href="<?= esc_url($url('admin/comments/edit?id=' . $id)) ?>" title="<?= esc_attr($body) ?>"><?= esc_html($body !== '' ? $body : t('comments.empty_body')) ?></a>
                <?php endif; ?>
                <?php if ($parentId > 0): ?>
                    <a class="badge" href="<?= esc_url($url('admin/comments/edit?id=' . $parentId)) ?>" title="<?= esc_attr(t('comments.child_of')) ?>">↳ <?= esc_html(t('comments.child_badge')) ?> #<?= (int)$parentId ?></a>
                <?php else: ?>
                    <span class="badge"><?= esc_html(t('comments.parent_badge')) ?></span>
                <?php endif; ?>
            </span>
            <div class="text-muted small">
                <?php if ($contentId > 0): ?>
                    <a href="<?= esc_url($url('admin/content/edit?id=' . $contentId)) ?>"><?= esc_html((string)($row['content_name'] ?? ('#' . $contentId))) ?></a>
                <?php else: ?>
                    <span>-</span>
                <?php endif; ?>
            </div>
            <div class="text-muted small">
                <?= esc_html($formatDateTime((string)($row['created'] ?? ''))) ?> - <?= esc_html(t('comments.statuses.' . $statusValue, ucfirst($statusValue))) ?> - <?= $repliesCount ?> <?= esc_html(t('comments.replies')) ?>
            </div>
        </td>
        <td class="mobile-hide"><?= esc_html($author !== '' ? $author : t('common.no_author')) ?></td>
        <td class="table-col-actions">
            <?php if (!$isTrash): ?>
                <button class="btn btn-light btn-icon" type="button" data-comments-toggle="<?= $id ?>" data-comments-mode="<?= $isPublished ? 'draft' : 'publish' ?>" aria-label="<?= esc_attr($isPublished ? t('comments.switch_to_draft') : t('comments.publish')) ?>" title="<?= esc_attr($isPublished ? t('comments.switch_to_draft') : t('comments.publish')) ?>">
                    <?= icon($isPublished ? 'hide' : 'show') ?>
                    <span class="sr-only"><?= esc_html($isPublished ? t('comments.switch_to_draft') : t('comments.publish')) ?></span>
                </button>
            <?php else: ?>
                <button class="btn btn-light btn-icon" type="button" data-comments-restore="<?= $id ?>" aria-label="<?= esc_attr(t('comments.restore')) ?>" title="<?= esc_attr(t('comments.restore')) ?>">
                    <?= icon('restore') ?>
                    <span class="sr-only"><?= esc_html(t('comments.restore')) ?></span>
                </button>
            <?php endif; ?>
            <button class="btn btn-light btn-icon" type="button" data-comments-delete-open="<?= $id ?>" data-comments-delete-mode="<?= $isTrash ? 'hard' : 'soft' ?>" aria-label="<?= esc_attr(t('comments.delete')) ?>" title="<?= esc_attr(t('comments.delete')) ?>">
                <?= icon('delete') ?>
                <span class="sr-only"><?= esc_html(t('comments.delete')) ?></span>
            </button>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = t('comments.search_placeholder');
$list['rootAttrs'] = ['data-content-edit-base' => $url('admin/content/edit?id=')];
$list['columns'] = [
    ['label' => t('comments.comment')],
    ['label' => t('common.author'), 'class' => 'mobile-hide'],
    ['label' => t('common.actions'), 'class' => 'table-col-actions table-col-actions-wide'],
];
$list['tableClass'] = 'admin-list-table';
$list['rowRenderer'] = $rowRenderer;

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
