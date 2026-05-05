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
    $statusValue = (string)($row['status'] ?? 'draft');
    $isPublished = $statusValue === 'published';
    $isTrash = $statusValue === 'trash';
    $statusIcon = match ($statusValue) {
        'published' => 'check',
        'trash' => 'delete',
        default => 'clock',
    };
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
            <div class="comment-list-author">
                <?= get_avatar($row, 'user-list-avatar comment-list-avatar mobile-hide', 40, true) ?>
                <div class="comment-list-author-main">
                    <a class="admin-list-truncate" href="<?= esc_url($url('admin/comments/edit?id=' . $id)) ?>"><?= esc_html($author !== '' ? $author : t('common.no_author')) ?></a>
                    <div class="comment-list-meta text-muted small">
                        <?= icon($parentId > 0 ? 'reply' : 'content', 'comment-list-meta-icon icon') ?>
                        <?php if ($parentId > 0): ?>
                            <a href="<?= esc_url($url('admin/comments/edit?id=' . $parentId)) ?>"><?= esc_html(t('comments.reacts_to_comment')) ?></a>
                        <?php elseif ($contentId > 0): ?>
                            <a href="<?= esc_url($url('admin/content/edit?id=' . $contentId)) ?>"><?= esc_html((string)($row['content_name'] ?? ('#' . $contentId))) ?></a>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </div>
                    <div class="comment-list-meta text-muted small">
                        <?= icon('calendar', 'comment-list-meta-icon icon') ?>
                        <span><?= esc_html($formatDateTime((string)($row['created'] ?? ''))) ?></span>
                    </div>
                </div>
            </div>
        </td>
        <td>
            <div class="comment-list-body">
                <a class="comment-list-body-text" href="<?= esc_url($url('admin/comments/edit?id=' . $id)) ?>" title="<?= esc_attr($body) ?>"><?= esc_html($body !== '' ? $body : t('comments.empty_body')) ?></a>
                <span class="badge comment-list-status"><?= icon($statusIcon, 'comment-list-status-icon icon') ?><?= esc_html(t('comments.statuses.' . $statusValue, ucfirst($statusValue))) ?></span>
            </div>
        </td>
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
$list['columns'] = [
    ['label' => t('common.author'), 'class' => 'comment-list-author-col'],
    ['label' => t('comments.comment')],
    ['label' => t('common.actions'), 'class' => 'table-col-actions table-col-actions-wide'],
];
$list['tableClass'] = 'admin-list-table comment-list-table';
$list['rowRenderer'] = $rowRenderer;

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
