<?php
$listState = \App\View\Admin\ListConfigFactory::state($pagination, $status ?? 'all', $query ?? '', $statusCounts ?? []);
$listItems = $listState['items'];
$listPage = $listState['page'];
$listPerPage = $listState['perPage'];
$listTotalPages = $listState['totalPages'];
$statusCurrent = $listState['statusCurrent'];
$listQuery = $listState['query'];
$statusCounts = $listState['statusCounts'];
$statusLinks = ['all' => $t('common.all') . ' (' . (int)($statusCounts['all'] ?? 0) . ')'];
foreach ($availableStatuses as $statusValue) {
    $statusLinks[$statusValue] = $t('content.statuses.' . $statusValue, ucfirst($statusValue)) . ' (' . (int)($statusCounts[$statusValue] ?? 0) . ')';
}
$authUser = $_SESSION['auth'] ?? [];
$isEditor = (string)($authUser['role'] ?? '') === 'editor';
$currentUserId = (int)($authUser['id'] ?? 0);

$rowRenderer = static function (array $row) use ($url, $formatDateTime, $icon, $t, $isEditor, $currentUserId, $csrfField): string {
    $id = (int)($row['id'] ?? 0);
    $createdAtRaw = (string)($row['created'] ?? '');
    $createdAt = $formatDateTime($createdAtRaw);
    $createdStamp = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;
    $isPlanned = $createdStamp !== false && $createdStamp > time();
    $statusValue = (string)($row['status'] ?? '');
    $canManage = !$isEditor || (int)($row['author'] ?? 0) === $currentUserId;
    $isPublished = $statusValue === 'published';
    ob_start();
    ?>
    <tr>
        <td>
            <?php $statusIcon = $statusValue === 'published' ? 'success' : ($statusValue === 'draft' ? 'concept' : ''); ?>
            <span class="d-flex align-center gap-2">
                <?php if ($statusIcon !== ''): ?><?= $icon($statusIcon) ?><?php endif; ?>
                <?php if ($canManage): ?>
                    <a href="<?= htmlspecialchars($url('admin/content/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </span>
            <div class="text-muted small"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($isPlanned): ?><div class="mt-2"><span class="badge text-bg-warning"><?= htmlspecialchars($t('content.planned'), ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
        </td>
        <td class="mobile-hide"><?= htmlspecialchars((string)($row['author_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="table-col-actions">
            <?php if ($canManage): ?>
                <form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $id . '/status'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                    <?= $csrfField() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="mode" value="<?= $isPublished ? 'draft' : 'publish' ?>">
                    <button class="btn btn-light btn-icon" type="button" data-content-toggle="<?= $id ?>" data-content-mode="<?= $isPublished ? 'draft' : 'publish' ?>" aria-label="<?= htmlspecialchars($isPublished ? $t('content.switch_to_draft') : $t('content.publish'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($isPublished ? $t('content.switch_to_draft') : $t('content.publish'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= $icon($isPublished ? 'hide' : 'show') ?>
                        <span class="sr-only"><?= htmlspecialchars($isPublished ? $t('content.switch_to_draft') : $t('content.publish'), ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                </form>
                <button class="btn btn-light btn-icon" type="button" data-content-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('delete') ?>
                    <span class="sr-only"><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
    return (string)ob_get_clean();
};

$listConfig = \App\View\Admin\ListConfigFactory::build([
    'name' => 'content',
    'endpoint' => $url('admin/api/v1/content'),
    'editBase' => $url('admin/content/edit?id='),
    'csrfMarkup' => $csrfField(),
    'statusCurrent' => $statusCurrent,
    'statusLinks' => $statusLinks,
    'statusUrl' => static fn(string $targetStatus): string => $url('admin/content?status=' . urlencode($targetStatus) . '&per_page=' . $listPerPage . '&page=1'),
    'searchPlaceholder' => $t('content.search_placeholder'),
    'query' => $listQuery,
    'columns' => [
        ['label' => $t('common.name')],
        ['label' => $t('common.author'), 'class' => 'mobile-hide'],
        ['label' => $t('common.actions'), 'class' => 'table-col-actions'],
    ],
    'rowRenderer' => $rowRenderer,
    'page' => $listPage,
    'perPage' => $listPerPage,
    'totalPages' => $listTotalPages,
    'allowedPerPage' => $allowedPerPage,
    'paginationUrl' => static fn(int $targetPage): string => $url('admin/content?page=' . $targetPage . '&per_page=' . $listPerPage . '&status=' . urlencode($statusCurrent) . '&q=' . urlencode($listQuery)),
    'deleteConfirmText' => $t('content.delete_confirm'),
]);

require __DIR__ . '/../partials/list-layout.php';
