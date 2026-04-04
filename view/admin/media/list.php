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
            <input class="search-input" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Hledat název nebo cestu" data-media-search>
            <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
        </div>
    </form>
</div>

<div class="card p-2">
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>Médium</th><th>Autor</th><th class="table-col-actions">Akce</th>
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
                                <a href="<?= htmlspecialchars($url('admin/media/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                <div class="text-muted"><?= htmlspecialchars((string)($row['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted"><?= htmlspecialchars((string)($row['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars((string)($row['author_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="table-col-actions">
                            <button class="btn btn-light btn-icon" type="button" data-media-delete-open="<?= $id ?>" aria-label="Smazat médium" title="Smazat médium">
                                <?= $icon('delete') ?>
                                <span class="sr-only">Smazat médium</span>
                            </button>
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
                <a class="pagination-link<?= $page <= 1 ? ' disabled' : '' ?>" data-media-prev href="<?= htmlspecialchars($url('admin/media?page=' . $prevPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $icon('prev') ?><span>Předchozí</span></a>
                <a class="pagination-link<?= $page >= $totalPages ? ' disabled' : '' ?>" data-media-next href="<?= htmlspecialchars($url('admin/media?page=' . $nextPage . '&per_page=' . $perPage . '&q=' . urlencode($query)), ENT_QUOTES, 'UTF-8') ?>"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><span>Další</span><?= $icon('next') ?></a>
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
            <button class="btn btn-light" type="submit">Použít</button>
        </form>
    </div>
</div>

<div class="modal-overlay" data-media-delete-modal>
    <div class="modal">
        <p>Skutečně smazat toto médium?</p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-media-delete-cancel>Zrušit</button>
            <button class="btn btn-primary" type="button" data-media-delete-confirm>Potvrdit</button>
        </div>
    </div>
</div>
</div>
