<?php
if (!defined('BASE_DIR')) {
    exit;
}

$mode = (string)($mode ?? 'modal');
?>
<?php if ($mode === 'items'): ?>
    <?php
    $items = is_array($items ?? null) ? $items : [];
    $page = max(1, (int)($page ?? 1));
    $totalPages = max(1, (int)($totalPages ?? 1));
    ?>
    <div data-media-library-items data-page="<?= $page ?>" data-total-pages="<?= $totalPages ?>">
        <?php if ($items === []): ?>
            <p class="text-muted m-0"><?= esc_html(t('media.no_results')) ?></p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                $id = (int)($item['id'] ?? 0);
                $name = (string)($item['name'] ?? t('media.untitled'));
                $previewPath = (string)($item['preview_path'] ?? '');
                ?>
                <button
                    class="media-library-card"
                    type="button"
                    data-media-library-select="<?= $id ?>"
                    data-media-name="<?= esc_attr($name) ?>"
                    data-media-path="<?= esc_attr((string)($item['path'] ?? '')) ?>"
                    data-media-created="<?= esc_attr((string)($item['created'] ?? '')) ?>"
                    data-media-created-label="<?= esc_attr((string)($item['created_label'] ?? ($item['created'] ?? ''))) ?>"
                    data-media-preview-path="<?= esc_attr($previewPath) ?>"
                    data-media-can-edit="<?= ($item['can_edit'] ?? false) === true ? '1' : '0' ?>"
                    data-media-can-delete="<?= ($item['can_delete'] ?? false) === true ? '1' : '0' ?>"
                    data-media-webp-path="<?= esc_attr((string)($item['webp_path'] ?? '')) ?>"
                >
                    <div class="media-library-card-image">
                        <?php if ($previewPath !== ''): ?>
                            <img src="<?= esc_url($url($previewPath)) ?>" alt="<?= esc_attr($name) ?>">
                        <?php else: ?>
                            <div class="media-library-card-empty"></div>
                        <?php endif; ?>
                    </div>
                    <span class="media-library-card-check"><?= icon('check') ?></span>
                </button>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $uploadId = 'media-picker-upload';
    $uploadTypesLabel = (string)($imageUploadTypesLabel ?? '');
    $allowedTypes = $uploadTypesLabel !== '' ? str_replace('%s', $uploadTypesLabel, t('common.allowed_upload_types')) : '';
    ?>
    <div class="media-library-modal modal-overlay" data-media-library-modal data-media-library-per-page="10">
        <div class="media-library-modal-dialog modal">
            <div class="media-library-modal-header">
                <strong><?= esc_html(t('admin.menu.media')) ?></strong>
                <button class="btn btn-light btn-icon" type="button" data-ui-modal-close aria-label="<?= esc_attr(t('common.close')) ?>">
                    <?= icon('cancel') ?>
                </button>
            </div>
            <div class="media-library-modal-layout">
                <div class="media-library-detail">
                    <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                    <div class="media-library-detail-meta">
                        <div>
                            <label><?= esc_html(t('common.name')) ?></label>
                            <div class="d-flex gap-2">
                                <input type="text" value="" data-media-library-detail-name-input>
                                <button class="btn btn-light" type="button" data-media-library-rename disabled><?= esc_html(t('common.save')) ?></button>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted" data-media-library-status></small>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="button" data-media-library-choose disabled><?= esc_html(t('content.choose')) ?></button>
                        <button class="btn btn-danger" type="button" data-media-library-delete-open disabled><?= esc_html(t('common.delete')) ?></button>
                    </div>
                </div>
                <div class="media-library-list">
                    <form class="media-library-search" data-media-library-search>
                        <div class="search-field field-with-icon">
                            <input class="search-input" type="search" name="q" placeholder="<?= esc_attr(t('content.search_image')) ?>">
                            <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= icon('search') ?></span>
                        </div>
                    </form>
                    <form class="media-library-upload" method="post" enctype="multipart/form-data" action="" data-media-library-upload-form>
                        <?= $csrfField() ?>
                        <input type="hidden" name="content_id" value="">
                        <div class="custom-upload-field" data-media-library-upload-field>
                            <label class="btn btn-light custom-upload-button" for="<?= esc_attr($uploadId) ?>">
                                <span class="custom-upload-main-icon" data-custom-upload-icon><?= icon('upload') ?></span>
                                <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= esc_attr(t('common.upload_add_files')) ?>"><?= esc_html(t('common.upload_add_files')) ?></span>
                                <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= icon('loader') ?></span>
                            </label>
                            <input id="<?= esc_attr($uploadId) ?>" type="file" name="thumbnail" accept="<?= esc_attr((string)($imageUploadAccept ?? '')) ?>" required>
                        </div>
                        <?php if ($allowedTypes !== ''): ?>
                            <small class="text-muted d-block mt-2"><?= esc_html($allowedTypes) ?></small>
                        <?php endif; ?>
                    </form>
                    <div class="media-library-grid" data-media-library-grid></div>
                    <div class="pagination pagination-centered">
                        <a class="pagination-link disabled" href="#" data-media-library-prev aria-disabled="true" tabindex="-1"><?= icon('prev') ?><span><?= esc_html(t('common.previous')) ?></span></a>
                        <a class="pagination-link disabled" href="#" data-media-library-next aria-disabled="true" tabindex="-1"><span><?= esc_html(t('common.next')) ?></span><?= icon('next') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
