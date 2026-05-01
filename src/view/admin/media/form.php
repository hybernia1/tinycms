<?php
if (!defined('BASE_DIR')) {
    exit;
}

$previewPath = (string)($item['path'] ?? '');
$previewUrl = $previewPath !== '' ? $url($previewPath) : '';
$fileMeta = null;
if ($mode === 'edit') {
    $metaPath = trim((string)($item['path'] ?? ''));
    $absolutePath = BASE_DIR . '/' . ltrim($metaPath, '/');
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
    action="<?= esc_url($mode === 'add' ? $url('admin/api/v1/media/add') : $url('admin/api/v1/media/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label><?= esc_html(t('common.name')) ?></label>
                <input type="text" name="name" value="<?= esc_attr((string)($item['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= esc_html((string)$errors['name']) ?></small><?php endif; ?>
            </div>

            <?php if ($mode === 'add'): ?>
                <div class="mb-3">
                    <label><?= esc_html(t('media.file')) ?></label>
                    <div class="custom-upload-field" data-custom-upload-auto-submit>
                        <label class="btn btn-light custom-upload-button" for="media-file-upload">
                            <span class="custom-upload-main-icon" data-custom-upload-icon><?= icon('upload') ?></span>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= esc_attr(t('common.upload_add_files')) ?>"><?= esc_html(t('common.upload_add_files')) ?></span>
                            <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= icon('loader') ?></span>
                        </label>
                        <input id="media-file-upload" type="file" name="file" accept="<?= esc_attr((string)($imageUploadAccept ?? '')) ?>" required>
                    </div>
                    <small class="text-muted d-block mt-2"><?= esc_html(sprintf(t('common.allowed_upload_types'), (string)($imageUploadTypesLabel ?? ''))) ?></small>
                    <?php if (!empty($errors['file'])): ?><small class="text-danger"><?= esc_html((string)$errors['file']) ?></small><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'edit'): ?>
                <?php if ($previewUrl !== ''): ?>
                    <div class="media-picker-preview mb-3">
                        <img src="<?= esc_url($previewUrl) ?>" alt="<?= esc_attr((string)($item['name'] ?? '')) ?>">
                    </div>
                <?php endif; ?>
                <h3 class="mb-3"><?= esc_html(t('media.used_in')) ?></h3>
                <?php if (($usages ?? []) === []): ?>
                    <p class="text-muted m-0"><?= esc_html(t('media.no_usage')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th><?= esc_html(t('content.post')) ?></th><th><?= esc_html(t('common.created')) ?></th><th><?= esc_html(t('media.usage_origin')) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($usages as $usage): ?>
                                <tr>
                                    <td>
                                        <a href="<?= esc_url($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0))) ?>">
                                            <?= esc_html((string)($usage['name'] ?? '')) ?>
                                        </a>
                                    </td>
                                    <td><?= esc_html($formatDateTime((string)($usage['created'] ?? ''))) ?></td>
                                    <?php
                                    $origins = [];
                                    if ((int)($usage['used_as_thumbnail'] ?? 0) === 1) {
                                        $origins[] = t('media.origin_thumbnail');
                                    }
                                    if ((int)($usage['used_in_body'] ?? 0) === 1) {
                                        $origins[] = t('media.origin_post_body');
                                    }
                                    ?>
                                    <td><?= esc_html(implode(', ', $origins)) ?></td>
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
                    <div class="content-box-header"><?= esc_html(t('common.actions')) ?></div>
                    <div class="p-3">
                        <div class="mb-3">
                            <label><?= esc_html(t('common.created')) ?></label>
                            <div class="text-muted"><?= esc_html($formatDateTime((string)($item['created'] ?? ''))) ?></div>
                        </div>
                        <div class="m-0">
                            <label><?= esc_html(t('common.author')) ?></label>
                            <div
                                class="tag-picker"
                                data-picker
                                data-picker-mode="single"
                                data-search-endpoint="<?= esc_attr($url('admin/api/v1/users/search')) ?>"
                                data-allow-empty="false"
                                data-empty-label="<?= esc_attr(t('common.no_author')) ?>"
                                data-no-results-label="<?= esc_attr(t('common.no_results')) ?>"
                                data-search-placeholder="<?= esc_attr(t('users.search_placeholder')) ?>"
                                data-selected-label="<?= esc_attr((string)($authorLabel ?? '')) ?>"
                            >
                                <input type="hidden" name="author" value="<?= esc_attr((string)($item['author'] ?? '')) ?>" data-picker-value>
                                <div class="tag-picker-field">
                                    <div class="tag-picker-chips" data-picker-chips></div>
                                    <input
                                        type="text"
                                        class="tag-picker-input"
                                        data-picker-input
                                        autocomplete="off"
                                        placeholder="<?= esc_attr(t('users.search_placeholder')) ?>"
                                    >
                                </div>
                                <div class="tag-picker-suggestions" data-picker-suggestions></div>
                            </div>
                            <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= esc_html((string)$errors['author']) ?></small><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($fileMeta !== null): ?>
                    <div class="card">
                        <div class="content-box-header"><?= esc_html(t('media.file')) ?></div>
                        <div class="p-3">
                            <div class="mb-3">
                                <label><?= esc_html(t('media.path')) ?></label>
                                <div class="text-muted"><?= esc_html((string)($item['path'] ?? '')) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= esc_html(t('media.filename')) ?></label>
                                <div class="text-muted"><?= esc_html((string)$fileMeta['filename']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= esc_html(t('media.mime')) ?></label>
                                <div class="text-muted"><?= esc_html((string)$fileMeta['mime']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= esc_html(t('media.extension')) ?></label>
                                <div class="text-muted"><?= esc_html((string)$fileMeta['extension']) ?></div>
                            </div>
                            <div class="mb-3">
                                <label><?= esc_html(t('media.dimensions')) ?></label>
                                <div class="text-muted"><?= esc_html((string)$fileMeta['dimensions']) ?></div>
                            </div>
                            <div class="m-0">
                                <label><?= esc_html(t('media.size')) ?></label>
                                <div class="text-muted"><?= esc_html((string)$fileMeta['size']) ?></div>
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
        action="<?= esc_url($url('admin/api/v1/media/' . (int)($item['id'] ?? 0) . '/delete')) ?>"
        data-api-submit
    >
        <?= $csrfField() ?>
    </form>
<?php endif; ?>
