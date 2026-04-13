<?php $createdAt = $formatInputDateTime((string)($item['created'] ?? '')); ?>
<?php
$initialTerms = array_values(array_filter(array_map(static fn($term): string => trim((string)$term), (array)($selectedTerms ?? []))));
$termsValue = implode(', ', $initialTerms);
$termsJson = $e(json_encode($initialTerms, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]');
?>
<?php
$thumbnailPath = trim((string)($item['thumbnail_path_webp'] ?? ''));
if ($thumbnailPath === '') {
    $thumbnailPath = trim((string)($item['thumbnail_path'] ?? ''));
}
$thumbnailUrl = $thumbnailPath !== '' ? $url($thumbnailPath) : '';
$contentId = (int)($item['id'] ?? 0);
?>
<form
    id="content-editor-form"
    class="content-editor-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= $e($mode === 'add' ? $url('admin/api/v1/content/add') : $url('admin/api/v1/content/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    <?= $mode === 'add' ? 'data-redirect-url="' . $e($url('admin/content')) . '"' : 'data-stay-on-page' ?>
    data-autosave-endpoint="<?= $e($url('admin/api/v1/content/autosave')) ?>"
    data-draft-init-endpoint="<?= $e($url('admin/api/v1/content/draft/init')) ?>"
    data-edit-url-base="<?= $e($url('admin/content/edit?id=')) ?>"
>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>" data-content-id-hidden>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <div class="d-flex align-center justify-between gap-2 mb-1">
                    <label><?= $e($t('common.name')) ?></label>
                    <?php if (!empty($hasAiProvider)): ?>
                        <button class="btn btn-light btn-xs" type="button" data-content-ai-open data-content-ai-target="name" data-modal-open data-modal-target="#content-ai-modal" data-content-ai-endpoint="<?= $e($url('admin/api/v1/content/ai/generate')) ?>">
                            <?= $icon('wand') ?>
                            <span>AI</span>
                        </button>
                    <?php endif; ?>
                </div>
                <input type="text" name="name" value="<?= $e((string)($item['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $e((string)$errors['name']) ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <div class="d-flex align-center justify-between gap-2 mb-1">
                    <label>Excerpt</label>
                    <?php if (!empty($hasAiProvider)): ?>
                        <button class="btn btn-light btn-xs" type="button" data-content-ai-open data-content-ai-target="excerpt" data-modal-open data-modal-target="#content-ai-modal" data-content-ai-endpoint="<?= $e($url('admin/api/v1/content/ai/generate')) ?>">
                            <?= $icon('wand') ?>
                            <span>AI</span>
                        </button>
                    <?php endif; ?>
                </div>
                <textarea name="excerpt" rows="3"><?= $e((string)($item['excerpt'] ?? '')) ?></textarea>
                <?php if (!empty($errors['excerpt'])): ?><small class="text-danger"><?= $e((string)$errors['excerpt']) ?></small><?php endif; ?>
            </div>
            <input type="hidden" name="status" value="<?= $e((string)($item['status'] ?? 'draft')) ?>" data-content-status-hidden>
            <div class="m-0">
                <div class="d-flex align-center justify-between gap-2 mb-1">
                    <label><?= $e($t('content.body')) ?></label>
                </div>
                <textarea
                    name="body"
                    rows="14"
                    data-wysiwyg
                    data-content-id="<?= $contentId ?>"
                    data-media-library-endpoint="<?= $e($url('admin/api/v1/content/' . $contentId . '/media')) ?>"
                    data-media-base-url="<?= $e($url('')) ?>"
                    data-link-title-endpoint="<?= $e($url('admin/api/v1/link-title')) ?>"
                ><?= $e((string)($item['body'] ?? '')) ?></textarea>
            </div>
        </div>
        <aside class="content-editor-sidebar">
            <div class="card">
                <div class="content-box-header"><?= $e($t('content.publication')) ?></div>
                <div class="p-3">
                    <div class="m-0">
                        <label><?= $e($t('content.publish_date')) ?></label>
                        <input type="datetime-local" name="created" value="<?= $e($createdAt) ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= $e((string)$errors['created']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>
                <div class="card">
                    <div class="content-box-header"><?= $e($t('common.author')) ?></div>
                    <div class="p-3">
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

            <div class="card">
                <div class="content-box-header"><?= $e($t('admin.menu.terms')) ?></div>
                <div class="p-3">
                    <?php if (!empty($hasAiProvider)): ?>
                        <div class="mb-2 d-flex justify-end">
                            <button class="btn btn-light btn-xs" type="button" data-content-ai-open data-content-ai-target="terms" data-modal-open data-modal-target="#content-ai-modal" data-content-ai-endpoint="<?= $e($url('admin/api/v1/content/ai/generate')) ?>">
                                <?= $icon('wand') ?>
                                <span>AI</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted"><?= $e($t('content.save_to_assign_tags')) ?></small>
                    <?php endif; ?>
                    <div
                        class="tag-picker"
                        data-tag-picker
                        data-suggest-endpoint="<?= $e($url('admin/api/v1/terms/suggest')) ?>"
                        data-initial="<?= $termsJson ?>"
                    >
                        <div class="tag-picker-field">
                            <div class="tag-picker-chips" data-tag-picker-chips></div>
                            <input class="tag-picker-input" type="text" data-tag-picker-input placeholder="<?= $e($t('content.find_or_add_tag')) ?>">
                        </div>
                        <div class="tag-picker-suggestions" data-tag-picker-suggestions></div>
                        <input type="hidden" name="terms" value="<?= $e($termsValue) ?>" data-tag-picker-value>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="content-box-header"><?= $e($t('content.thumbnail')) ?></div>
                <div class="p-3">
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted"><?= $e($t('content.thumbnail_hint')) ?></small>
                    <?php endif; ?>
                    <button
                        class="content-thumbnail-trigger mb-3<?= $thumbnailUrl === '' ? ' empty' : '' ?>"
                        type="button"
                        data-media-library-open
                        data-media-library-endpoint="<?= $e($url('admin/api/v1/content/' . $contentId . '/media')) ?>"
                        data-media-base-url="<?= $e($url('')) ?>"
                        data-current-media-id="<?= (int)($item['thumbnail'] ?? 0) ?>"
                    >
                        <?php if ($thumbnailUrl !== ''): ?>
                            <div class="content-thumbnail-preview">
                                <img src="<?= $e($thumbnailUrl) ?>" alt="<?= $e((string)($item['thumbnail_name'] ?? '')) ?>">
                            </div>
                        <?php else: ?>
                            <span><?= $e($t('content.choose_image')) ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </aside>
    </div>
    <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= $e((string)$errors['status']) ?></small><?php endif; ?>
</form>
<form
    id="content-delete-form"
    method="post"
    action="<?= $e($url('admin/api/v1/content/' . $contentId . '/delete')) ?>"
    data-api-submit
    data-redirect-url="<?= $e($url('admin/content')) ?>"
>
    <?= $csrfField() ?>
</form>
<div class="media-library-modal" data-media-library-modal data-media-library-per-page="<?= \App\Service\Support\PaginationConfig::perPage() ?>">
    <div class="media-library-modal-dialog">
        <div class="media-library-modal-header">
            <strong>Media library</strong>
            <button class="btn btn-light btn-icon" type="button" data-media-library-close aria-label="<?= $e($t('common.close')) ?>">
                <?= $icon('cancel') ?>
            </button>
        </div>
        <div class="media-library-modal-layout">
            <div class="media-library-detail">
                <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                <div class="media-library-detail-meta">
                    <div>
                        <label><?= $e($t('common.name')) ?></label>
                        <div class="d-flex gap-2">
                            <input type="text" value="" data-media-library-detail-name-input>
                            <button class="btn btn-light" type="button" data-media-library-rename disabled><?= $e($t('common.save')) ?></button>
                        </div>
                    </div>
                </div>
                <small class="text-muted" data-media-library-status></small>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="button" data-media-library-choose disabled><?= $e($t('content.choose')) ?></button>
                    <button
                        class="btn btn-danger"
                        type="button"
                        data-media-library-delete-open
                        data-modal-open
                        data-modal-target="#media-library-delete-modal"
                        data-type="<?= $e($t('content.image')) ?>"
                        data-form-id="media-library-delete-form"
                        disabled
                    >
                        <?= $e($t('common.delete')) ?>
                    </button>
                </div>
            </div>
            <div class="media-library-list">
                <form class="media-library-search" data-media-library-search>
                    <div class="search-field field-with-icon">
                        <input class="search-input" type="search" name="q" placeholder="<?= $e($t('content.search_image')) ?>">
                        <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= $icon('search') ?></span>
                    </div>
                </form>
                <form class="media-library-upload" method="post" enctype="multipart/form-data" action="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/upload')) ?>" data-media-library-upload-form>
                    <?= $csrfField() ?>
                    <input type="hidden" name="content_id" value="<?= $contentId ?>">
                    <div class="custom-upload-field" data-media-library-upload-field>
                        <label class="btn btn-light custom-upload-button" for="content-thumbnail-upload">
                            <span class="custom-upload-main-icon" data-custom-upload-icon><?= $icon('upload') ?></span>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= $e($t('common.upload_add_files')) ?>"><?= $e($t('common.upload_add_files')) ?></span>
                            <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= $icon('loader') ?></span>
                        </label>
                        <input id="content-thumbnail-upload" type="file" name="thumbnail" accept="<?= $e((string)($imageUploadAccept ?? '')) ?>" required>
                    </div>
                    <small class="text-muted d-block mt-2"><?= $e(sprintf($t('common.allowed_upload_types'), (string)($imageUploadTypesLabel ?? ''))) ?></small>
                </form>
                <div class="media-library-grid" data-media-library-grid></div>
                <div class="pagination pagination-centered">
                    <a class="pagination-link disabled" href="#" data-media-library-prev aria-disabled="true" tabindex="-1"><?= $icon('prev') ?><span><?= $e($t('common.previous')) ?></span></a>
                    <a class="pagination-link disabled" href="#" data-media-library-next aria-disabled="true" tabindex="-1"><span><?= $e($t('common.next')) ?></span><?= $icon('next') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="<?= $e($url('admin/api/v1/content/' . $contentId . '/thumbnail/0/select')) ?>" data-media-library-select-form data-action-template="<?= $e($url('admin/api/v1/content/' . $contentId . '/thumbnail/{mediaId}/select')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-media-id>
</form>
<form method="post" action="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/0/delete')) ?>" id="media-library-delete-form" data-action-template="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/delete')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-delete-media-id>
</form>
<form method="post" action="<?= $e($url('admin/api/v1/content/' . $contentId . '/thumbnail/detach')) ?>" data-media-library-detach-form>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
</form>
<form method="post" action="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/0/rename')) ?>" data-media-library-rename-form data-action-template="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/rename')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-rename-media-id>
    <input type="hidden" name="name" value="" data-media-library-rename-name>
</form>
<form method="post" action="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/0/attach')) ?>" data-media-library-attach-form data-action-template="<?= $e($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/attach')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-attach-media-id>
</form>
<div class="modal-overlay" data-modal id="media-library-delete-modal">
    <div class="modal">
        <p data-modal-text><?= $e($t('content.delete_image_confirm')) ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= $e($t('common.cancel')) ?></button>
            <button class="btn btn-primary" type="button" data-media-library-delete-confirm><?= $e($t('common.confirm')) ?></button>
        </div>
    </div>
</div>
<?php if (!empty($hasAiProvider)): ?>
<div class="modal-overlay" data-modal id="content-ai-modal">
    <div class="modal">
        <p class="mb-2"><?= $e($t('content.ai_modal_title')) ?></p>
        <div class="mb-3" data-content-ai-variants></div>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-content-ai-regenerate data-endpoint="<?= $e($url('admin/api/v1/content/ai/generate')) ?>" data-target="name"><?= $e($t('content.ai_regenerate')) ?></button>
            <button class="btn btn-light" type="button" data-modal-close><?= $e($t('common.close')) ?></button>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="modal-overlay" data-content-leave-modal>
    <div class="modal">
        <p><?= $e($t('content.leave_page_confirm')) ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-content-leave-cancel><?= $e($t('common.cancel')) ?></button>
            <button class="btn btn-primary" type="button" data-content-leave-confirm><?= $e($t('common.confirm')) ?></button>
        </div>
    </div>
</div>
<div class="modal-overlay" data-modal id="content-delete-modal">
    <div class="modal">
        <p data-modal-text><?= $e($t('content.delete_confirm')) ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= $e($t('common.cancel')) ?></button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="content-delete-form"><?= $e($t('common.confirm')) ?></button>
        </div>
    </div>
</div>
