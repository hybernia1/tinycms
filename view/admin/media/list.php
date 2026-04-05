<?php
$items = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$query = (string)($query ?? '');
$thumbSuffix = '_100x100.webp';
if (defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS)) {
    $firstVariant = MEDIA_THUMB_VARIANTS[0] ?? null;
    if (is_array($firstVariant) && !empty($firstVariant['suffix'])) {
        $thumbSuffix = (string)$firstVariant['suffix'];
    }
}
$authUser = $_SESSION['auth'] ?? [];
$isEditor = (string)($authUser['role'] ?? '') === 'editor';
$currentUserId = (int)($authUser['id'] ?? 0);
$csrfMarkup = $csrfField();
?>
<div data-media-list data-endpoint="<?= htmlspecialchars($url('admin/api/v1/media'), ENT_QUOTES, 'UTF-8') ?>" data-edit-base="<?= htmlspecialchars($url('admin/media/edit?id='), ENT_QUOTES, 'UTF-8') ?>" data-thumb-suffix="<?= htmlspecialchars($thumbSuffix, ENT_QUOTES, 'UTF-8') ?>">
<div data-media-csrf class="d-none"><?= $csrfMarkup ?></div>
<div class="d-flex justify-between align-center mb-3 admin-list-toolbar">
    <div></div>
    <form method="get" class="search-form">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <input type="hidden" name="page" value="1">
        <div class="search-field">
            <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('media.search_placeholder', 'Search name or path'), ENT_QUOTES, 'UTF-8') ?>" data-media-search>
            <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
        </div>
    </form>
</div>

<div class="card p-2">
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th><?= htmlspecialchars($t('admin.menu.media', 'Media'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('common.author', 'Author'), ENT_QUOTES, 'UTF-8') ?></th><th class="table-col-actions"><?= htmlspecialchars($t('common.actions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody data-media-list-body>
            <?php foreach ($items as $row):
                $id = (int)($row['id'] ?? 0);
                $previewPath = trim((string)($row['path_webp'] ?? ''));
                if ($previewPath !== '') {
                    $previewPath = (string)(preg_replace('/\.webp$/i', $thumbSuffix, $previewPath) ?? $previewPath);
                } else {
                    $previewPath = trim((string)($row['path'] ?? ''));
                }
                $previewUrl = $previewPath !== '' ? $url($previewPath) : '';
                $canManage = !$isEditor || (int)($row['author'] ?? 0) === $currentUserId;
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-center gap-2">
                            <?php if ($previewUrl !== ''): ?>
                                <div class="media-list-thumb">
                                    <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            <?php else: ?>
                                <div class="media-list-thumb media-list-thumb-empty"></div>
                            <?php endif; ?>
                            <div>
                                <?php if ($canManage): ?>
                                <a href="<?= htmlspecialchars($url('admin/media/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                <?php else: ?>
                                <span><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <div class="text-muted"><?= htmlspecialchars((string)($row['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted"><?= htmlspecialchars($formatDateTime((string)($row['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars((string)($row['author_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="table-col-actions">
                            <?php if ($canManage): ?>
                            <button class="btn btn-light btn-icon" type="button" data-media-delete-open="<?= $id ?>" aria-label="<?= htmlspecialchars($t('media.delete', 'Delete media'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('media.delete', 'Delete media'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon('delete') ?>
                                <span class="sr-only"><?= htmlspecialchars($t('media.delete', 'Delete media'), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                            <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-between align-center mt-4">
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php $prevPage = max(1, $page - 1); $nextPage = min($totalPages, $page + 1); ?>
                <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" data-media-prev href="<?= htmlspecialchars($url('admin/media?page=' . $prevPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" data-media-next href="<?= htmlspecialchars($url('admin/media?page=' . $nextPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
            </div>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <form method="get" class="d-flex gap-2 align-center">
            <select name="per_page" data-media-per-page>
                <?php foreach ($allowedPerPage as $option): ?>
                    <option value="<?= (int)$option ?>" <?= $perPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="page" value="1">
            <button class="btn btn-light" type="submit"><?= htmlspecialchars($t('common.apply', 'Apply'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
</div>

<div class="modal-overlay" data-media-delete-modal>
    <div class="modal">
        <p><?= htmlspecialchars($t('media.delete_confirm', 'Do you really want to delete this media?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-media-delete-cancel><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-media-delete-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
</div>
