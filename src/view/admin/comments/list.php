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
$list['statusLinks'] = $statusLinks;
$list['searchPlaceholder'] = t('comments.search_placeholder');
$list['rootAttrs'] = ['data-content-edit-base' => $url('admin/content/edit?id=')];
$list['columns'] = [
    ['label' => t('comments.comment')],
    ['label' => t('common.author'), 'class' => 'mobile-hide'],
    ['label' => t('common.actions'), 'class' => 'table-col-actions table-col-actions-wide'],
];
$list['tableClass'] = 'admin-list-table';

require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/list-layout.php';
