<?php
$previewPath = trim((string)($item['path_webp'] ?? ''));
if ($previewPath === '') {
    $previewPath = trim((string)($item['path'] ?? ''));
}
$previewUrl = $previewPath !== '' ? $url($previewPath) : '';
$authUser = $_SESSION['auth'] ?? [];
$isEditor = (string)($authUser['role'] ?? '') === 'editor';
$currentUserId = (int)($authUser['id'] ?? 0);
$fileMeta = null;
if ($mode === 'edit') {
    $metaPath = trim((string)($item['path'] ?? ''));
    if ($metaPath === '') {
        $metaPath = trim((string)($item['path_webp'] ?? ''));
    }
    $absolutePath = dirname(__DIR__, 3) . '/' . ltrim($metaPath, '/');
    if ($metaPath !== '' && is_file($absolutePath)) {
        $imageInfo = @getimagesize($absolutePath) ?: [0, 0, 'mime' => ''];
        $mime = trim((string)($imageInfo['mime'] ?? ''));
        if ($mime === '') {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = (string)@finfo_file($finfo, $absolutePath);
                @finfo_close($finfo);
            }
        }
        $sizeBytes = (int)(@filesize($absolutePath) ?: 0);
        $fileMeta = [
            'filename' => (string)basename($absolutePath),
            'extension' => strtolower((string)pathinfo($absolutePath, PATHINFO_EXTENSION)),
            'mime' => $mime,
            'size' => $sizeBytes > 0 ? ($sizeBytes >= 1048576 ? round($sizeBytes / 1048576, 2) . ' MB' : round($sizeBytes / 1024, 1) . ' KB') : '—',
            'dimensions' => ((int)($imageInfo[0] ?? 0) > 0 && (int)($imageInfo[1] ?? 0) > 0) ? ((int)$imageInfo[0] . ' × ' . (int)$imageInfo[1] . ' px') : '—',
        ];
    }
}
?>
<form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/media/add') : $url('admin/media/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label><?= htmlspecialchars($t('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>

            <?php if ($mode === 'add'): ?>
                <div class="mb-3">
                    <label><?= htmlspecialchars($t('media.file'), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="custom-upload-field" data-custom-upload-auto-submit>
                        <label class="btn btn-light custom-upload-button" for="media-file-upload">
                            <span class="custom-upload-main-icon" data-custom-upload-icon><?= $icon('upload') ?></span>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= htmlspecialchars($t('common.upload_add_files'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.upload_add_files'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= $icon('loader') ?></span>
                        </label>
                        <input id="media-file-upload" type="file" name="file" accept="<?= htmlspecialchars((string)($imageUploadAccept ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <small class="text-muted d-block mt-2"><?= htmlspecialchars(sprintf($t('common.allowed_upload_types'), (string)($imageUploadTypesLabel ?? '')), ENT_QUOTES, 'UTF-8') ?></small>
                    <?php if (!empty($errors['file'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['file'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'edit'): ?>
                <?php if ($previewUrl !== ''): ?>
                    <div class="content-thumbnail-preview mb-3">
                        <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endif; ?>
                <h3 class="mb-3"><?= htmlspecialchars($t('media.used_in'), ENT_QUOTES, 'UTF-8') ?></h3>
                <?php if (($usages ?? []) === []): ?>
                    <p class="text-muted m-0"><?= htmlspecialchars($t('media.no_usage'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th><?= htmlspecialchars($t('content.post'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('common.created'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($t('media.usage_origin'), ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($usages as $usage): ?>
                                <tr>
                                    <td>
                                        <a href="<?= htmlspecialchars($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)($usage['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($formatDateTime((string)($usage['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <?php
                                    $origins = [];
                                    if ((int)($usage['used_as_thumbnail'] ?? 0) === 1) {
                                        $origins[] = $t('media.origin_thumbnail');
                                    }
                                    if ((int)($usage['used_in_body'] ?? 0) === 1) {
                                        $origins[] = $t('media.origin_post_body');
                                    }
                                    ?>
                                    <td><?= htmlspecialchars(implode(', ', $origins), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <aside class="content-editor-sidebar">
            <div class="card">
                <div class="content-box-header"><?= htmlspecialchars($t('common.actions'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <?php if ($mode === 'edit'): ?>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('common.created'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars($formatDateTime((string)($item['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('common.updated'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars($formatDateTime((string)($item['updated'] ?? ''), '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($isEditor): ?>
                        <input type="hidden" name="author" value="<?= $currentUserId > 0 ? $currentUserId : '' ?>">
                    <?php else: ?>
                        <div class="m-0">
                            <label><?= htmlspecialchars($t('common.author'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select name="author">
                                <option value=""><?= htmlspecialchars($t('common.no_author'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($authors as $author): ?>
                                    <?php $authorId = (int)($author['ID'] ?? 0); ?>
                                    <option value="<?= $authorId ?>" <?= (int)($item['author'] ?? 0) === $authorId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)($author['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($author['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['author'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="content-box-footer d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/media'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.back'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if ($mode === 'edit'): ?>
                        <button class="btn btn-light" type="button" data-modal-open data-modal-target="#media-delete-modal"><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($mode === 'edit' && $fileMeta !== null): ?>
                <div class="card">
                    <div class="content-box-header"><?= htmlspecialchars($t('media.file'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="p-3">
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('media.path'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)($item['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('media.path_webp'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)($item['path_webp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('media.filename'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)$fileMeta['filename'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('media.mime'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)$fileMeta['mime'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('media.extension'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)$fileMeta['extension'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="mb-3">
                            <label><?= htmlspecialchars($t('media.dimensions'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)$fileMeta['dimensions'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="m-0">
                            <label><?= htmlspecialchars($t('media.size'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="text-muted"><?= htmlspecialchars((string)$fileMeta['size'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</form>

<?php if ($mode === 'edit'): ?>
    <form id="media-delete-form" method="post" action="<?= htmlspecialchars($url('admin/media/edit/delete?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
    </form>
    <div class="modal-overlay" data-modal id="media-delete-modal">
        <div class="modal">
            <p data-modal-text><?= htmlspecialchars($t('media.delete_confirm'), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-modal-close><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="media-delete-form"><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
<?php endif; ?>
