<?php
$previewPath = $media((string)($item['path'] ?? ''), 'small');
$previewUrl = $previewPath !== '' ? $url($previewPath) : '';
$fileMeta = null;
if ($mode === 'edit') {
    $metaPath = trim((string)($item['path'] ?? ''));
    $absolutePath = dirname(__DIR__, 4) . '/' . ltrim($metaPath, '/');
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
<form
    id="media-form"
    class="content-editor-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= $e($mode === 'add' ? $url('admin/api/v1/media/add') : $url('admin/api/v1/media/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label><?= $e($t('common.name')) ?></label>
                <input type="text" name="name" value="<?= $e((string)($item['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $e((string)$errors['name']) ?></small><?php endif; ?>
            </div>

            <?php if ($mode === 'add'): ?>
                <div class="mb-3">
                    <label><?= $e($t('media.file')) ?></label>
                    <div class="custom-upload-field" data-custom-upload-auto-submit>
                        <label class="btn btn-light custom-upload-button" for="media-file-upload">
                            <span class="custom-upload-main-icon" data-custom-upload-icon><?= $icon('upload') ?></span>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= $e($t('common.upload_add_files')) ?>"><?= $e($t('common.upload_add_files')) ?></span>
                            <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= $icon('loader') ?></span>
                        </label>
                        <input id="media-file-upload" type="file" name="file" accept="<?= $e((string)($imageUploadAccept ?? '')) ?>" required>
                    </div>
                    <small class="text-muted d-block mt-2"><?= $e(sprintf($t('common.allowed_upload_types'), (string)($imageUploadTypesLabel ?? ''))) ?></small>
                    <?php if (!empty($errors['file'])): ?><small class="text-danger"><?= $e((string)$errors['file']) ?></small><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'edit'): ?>
                <?php if ($previewUrl !== ''): ?>
                    <div class="content-thumbnail-preview mb-3">
                        <img src="<?= $e($previewUrl) ?>" alt="<?= $e((string)($item['name'] ?? '')) ?>">
                    </div>
                <?php endif; ?>
                <h3 class="mb-3"><?= $e($t('media.used_in')) ?></h3>
                <?php if (($usages ?? []) === []): ?>
                    <p class="text-muted m-0"><?= $e($t('media.no_usage')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th><?= $e($t('content.post')) ?></th><th><?= $e($t('common.created')) ?></th><th><?= $e($t('media.usage_origin')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($usages as $usage): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $e($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0))) ?>">
                                            <?= $e((string)($usage['name'] ?? '')) ?>
                                        </a>
                                    </td>
                                    <td><?= $e($formatDateTime((string)($usage['created'] ?? ''))) ?></td>
                                    <?php
                                    $origins = [];
                                    if ((int)($usage['used_as_thumbnail'] ?? 0) === 1) {
                                        $origins[] = $t('media.origin_thumbnail');
                                    }
                                    if ((int)($usage['used_in_body'] ?? 0) === 1) {
                                        $origins[] = $t('media.origin_post_body');
                                    }
                                    ?>
                                    <td><?= $e(implode(', ', $origins)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($mode === 'edit'): ?>
            <aside class="content-editor-sidebar">
                <div class="card">
                    <div class="content-box-header"><?= $e($t('common.actions')) ?></div>
                    <div class="p-3">
                        <div class="mb-3">
                            <label><?= $e($t('common.created')) ?></label>
                            <div class="text-muted"><?= $e($formatDateTime((string)($item['created'] ?? ''))) ?></div>
                        </div>
                        <div class="m-0">
                            <label><?= $e($t('common.author')) ?></label>
                            <select name="author">
                                <option value=""><?= $e($t('common.no_author')) ?></option>
                                <?php foreach ($authors as $author): ?>
                                    <?php $authorId = (int)($author['ID'] ?? 0); ?>
                                    <option value="<?= $authorId ?>" <?= (int)($item['author'] ?? 0) === $authorId ? 'selected' : '' ?>>
                                        <?= $e((string)($author['name'] ?? '')) ?> (<?= $e((string)($author['email'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= $e((string)$errors['author']) ?></small><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($fileMeta !== null): ?>
                    <div class="card">
                        <div class="content-box-header"><?= $e($t('media.file')) ?></div>
                        <div class="p-3">
                            <div class="mb-3">
                                <label><?= $e($t('media.path')) ?></label>
                                <div class="text-muted"><?= $e((string)($item['path'] ?? '')) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= $e($t('media.filename')) ?></label>
                                <div class="text-muted"><?= $e((string)$fileMeta['filename']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= $e($t('media.mime')) ?></label>
                                <div class="text-muted"><?= $e((string)$fileMeta['mime']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= $e($t('media.extension')) ?></label>
                                <div class="text-muted"><?= $e((string)$fileMeta['extension']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= $e($t('media.dimensions')) ?></label>
                                <div class="text-muted"><?= $e((string)$fileMeta['dimensions']) ?></div>
                            </div>
                            <div class="m-0">
                                <label><?= $e($t('media.size')) ?></label>
                                <div class="text-muted"><?= $e((string)$fileMeta['size']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
    </div>
</form>

<?php if ($mode === 'edit'): ?>
    <form
        id="media-delete-form"
        method="post"
        action="<?= $e($url('admin/api/v1/media/' . (int)($item['id'] ?? 0) . '/delete')) ?>"
        data-api-submit
    >
        <?= $csrfField() ?>
    </form>
    <div class="modal-overlay" data-modal id="media-delete-modal">
        <div class="modal">
            <p data-modal-text><?= $e($t('media.delete_confirm')) ?></p>
            <div class="modal-actions">
                <button class="btn btn-light" type="button" data-modal-close><?= $e($t('common.cancel')) ?></button>
                <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="media-delete-form"><?= $e($t('common.confirm')) ?></button>
            </div>
        </div>
    </div>
<?php endif; ?>
