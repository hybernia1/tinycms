<div class="media-library-modal" data-media-library-modal data-media-library-per-page="<?= \App\Service\Support\PaginationConfig::perPage() ?>">
    <div class="media-library-modal-dialog">
        <div class="media-library-modal-header">
            <strong>Media library</strong>
            <button class="btn btn-light btn-icon" type="button" data-media-library-close aria-label="<?= htmlspecialchars($t('common.close', 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('cancel') ?>
            </button>
        </div>
        <div class="media-library-modal-layout">
            <div class="media-library-detail">
                <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                <div class="media-library-detail-meta">
                    <div>
                        <label><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="d-flex gap-2">
                            <input type="text" value="" data-media-library-detail-name-input>
                            <button class="btn btn-light" type="button" data-media-library-rename disabled><?= htmlspecialchars($t('common.save', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </div>
                    <div><strong><?= htmlspecialchars($t('common.created', 'Created'), ENT_QUOTES, 'UTF-8') ?>:</strong> <span data-media-library-detail-created>—</span></div>
                </div>
                <small class="text-muted" data-media-library-status></small>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="button" data-media-library-choose disabled><?= htmlspecialchars($t('content.choose', 'Choose'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button
                        class="btn btn-danger"
                        type="button"
                        data-media-library-delete-open
                        data-modal-open
                        data-modal-target="#media-library-delete-modal"
                        data-type="<?= htmlspecialchars($t('content.image', 'image'), ENT_QUOTES, 'UTF-8') ?>"
                        data-form-id="media-library-delete-form"
                        disabled
                    >
                        <?= htmlspecialchars($t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
            <div class="media-library-list">
                <form class="media-library-search" data-media-library-search>
                    <div class="search-field field-with-icon">
                        <input class="search-input" type="search" name="q" placeholder="<?= htmlspecialchars($t('content.search_image', 'Search image'), ENT_QUOTES, 'UTF-8') ?>">
                        <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= $icon('search') ?></span>
                    </div>
                </form>
                <form class="media-library-upload" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/upload'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-upload-form>
                    <?= $csrfField() ?>
                    <input type="hidden" name="content_id" value="<?= $contentId ?>">
                    <div class="custom-upload-field" data-media-library-upload-field>
                        <label class="btn btn-light custom-upload-button" for="content-thumbnail-upload">
                            <span class="custom-upload-main-icon" data-custom-upload-icon><?= $icon('upload') ?></span>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= htmlspecialchars($t('common.upload_add_files', 'Add files'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.upload_add_files', 'Add files'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= $icon('loader') ?></span>
                        </label>
                        <input id="content-thumbnail-upload" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                    </div>
                </form>
                <div class="media-library-grid" data-media-library-grid></div>
                <div class="pagination pagination-centered">
                    <a class="pagination-link disabled" href="#" data-media-library-prev aria-disabled="true" tabindex="-1"><?= $icon('prev') ?><span><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></span></a>
                    <a class="pagination-link disabled" href="#" data-media-library-next aria-disabled="true" tabindex="-1"><span><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></span><?= $icon('next') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/thumbnail/0/select'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-select-form data-action-template="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/thumbnail/{mediaId}/select'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-media-id>
</form>
<form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/0/delete'), ENT_QUOTES, 'UTF-8') ?>" id="media-library-delete-form" data-action-template="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/delete'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-delete-media-id>
</form>
<form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/thumbnail/detach'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-detach-form>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
</form>
<form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/0/rename'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-rename-form data-action-template="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/rename'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-rename-media-id>
    <input type="hidden" name="name" value="" data-media-library-rename-name>
</form>
<form method="post" action="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/0/attach'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-attach-form data-action-template="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/attach'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-attach-media-id>
</form>
<?php
$modal = [
    'id' => 'media-library-delete-modal',
    'attributes' => ['data-modal' => null],
    'message' => $t('content.delete_image_confirm', 'Do you really want to delete this image?'),
    'message_attributes' => ['data-modal-text' => null],
    'confirm_attributes' => ['type' => 'button', 'data-media-library-delete-confirm' => null],
];
require __DIR__ . '/confirm.php';
?>
