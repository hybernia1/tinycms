<?php
if (!defined('BASE_DIR')) {
    exit;
}
 $createdAt = $formatInputDateTime((string)($item['created'] ?? '')); ?>
<?php
$initialTerms = array_values(array_filter(array_map(static fn($term): string => trim((string)$term), (array)($selectedTerms ?? []))));
$termsValue = implode(', ', $initialTerms);
$termsJson = $escHtml(json_encode($initialTerms, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]');
?>
<?php
$thumbnailPath = $media((string)($item['thumbnail_path'] ?? ''), 'small');
$thumbnailUrl = $thumbnailPath !== '' ? $url($thumbnailPath) : '';
$contentId = (int)($item['id'] ?? 0);
$previewSlug = $contentId > 0 ? (new \App\Service\Support\Slugger())->slug((string)($item['name'] ?? ''), $contentId) : '';
$previewUrl = $previewSlug !== '' ? $url($previewSlug . '?preview=1') : '';
$previewTarget = 'tinycms-content-preview' . ($contentId > 0 ? '-' . $contentId : '');
$previewHidden = $previewUrl === '';
?>
<form
    id="content-editor-form"
    class="content-editor-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= $escUrl($mode === 'add' ? $url('admin/api/v1/content/add') : $url('admin/api/v1/content/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    <?= $mode === 'edit' ? 'data-stay-on-page' : '' ?>
    data-autosave-endpoint="<?= $escHtml($url('admin/api/v1/content/autosave')) ?>"
    data-draft-init-endpoint="<?= $escHtml($url('admin/api/v1/content/draft/init')) ?>"
    data-edit-url-base="<?= $escHtml($url('admin/content/edit?id=')) ?>"
>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>" data-content-id-hidden>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label><?= $escHtml($t('common.name')) ?></label>
                <input type="text" name="name" value="<?= $escHtml((string)($item['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $escHtml((string)$errors['name']) ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3"><?= $escHtml((string)($item['excerpt'] ?? '')) ?></textarea>
                <?php if (!empty($errors['excerpt'])): ?><small class="text-danger"><?= $escHtml((string)$errors['excerpt']) ?></small><?php endif; ?>
            </div>
            <input type="hidden" name="status" value="<?= $escHtml((string)($item['status'] ?? 'draft')) ?>" data-content-status-hidden>
            <div class="m-0">
                <textarea
                    name="body"
                    rows="14"
                    data-wysiwyg
                    data-content-id="<?= $contentId ?>"
                    data-media-library-endpoint="<?= $escHtml($url('admin/api/v1/content/' . $contentId . '/media')) ?>"
                    data-media-base-url="<?= $escHtml($url('')) ?>"
                    data-link-title-endpoint="<?= $escHtml($url('admin/api/v1/content/link-title')) ?>"
                ><?= $escHtml((string)($item['body'] ?? '')) ?></textarea>
            </div>
        </div>
        <aside class="content-editor-sidebar">
            <div class="card">
                <div class="content-box-header content-box-header-actions">
                    <span><?= $escHtml($t('content.publication')) ?></span>
                    <a
                        class="btn btn-light"
                        href="<?= $escUrl($previewUrl) ?>"
                        target="<?= $escHtml($previewTarget) ?>"
                        data-content-preview-link
                        <?= $previewHidden ? 'hidden' : '' ?>
                    >
                        <?= $escHtml($t('content.preview')) ?>
                    </a>
                </div>
                <div class="p-3">
                    <div class="mb-3">
                        <label><?= $escHtml($t('content.publish_date')) ?></label>
                        <input type="datetime-local" name="created" value="<?= $escHtml($createdAt) ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= $escHtml((string)$errors['created']) ?></small><?php endif; ?>
                    </div>
                    <div class="mt-3 mb-3">
                        <label><?= $escHtml($t('content.type')) ?></label>
                        <?php $selectedType = (string)($item['type'] ?? \App\Service\Application\Content::TYPE_ARTICLE); ?>
                        <select name="type" required>
                            <?php foreach ((array)($contentTypes ?? []) as $typeOption): ?>
                                <?php $typeKey = (string)$typeOption; ?>
                                <option value="<?= $escHtml($typeKey) ?>" <?= $selectedType === $typeKey ? 'selected' : '' ?>>
                                    <?= $escHtml($t('content.types.' . $typeKey, ucfirst(str_replace('_', ' ', $typeKey)))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['type'])): ?><small class="text-danger"><?= $escHtml((string)$errors['type']) ?></small><?php endif; ?>
                    </div>
                    <div class="mt-3 mb-3">
                        <label><?= $escHtml($t('common.author')) ?></label>
                        <select name="author">
                            <option value=""><?= $escHtml($t('common.no_author')) ?></option>
                            <?php foreach ($authors as $author): ?>
                                <?php $authorId = (int)($author['ID'] ?? 0); ?>
                                <option value="<?= $authorId ?>" <?= (int)($item['author'] ?? 0) === $authorId ? 'selected' : '' ?>>
                                    <?= $escHtml((string)($author['name'] ?? '')) ?> (<?= $escHtml((string)($author['email'] ?? '')) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= $escHtml((string)$errors['author']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="content-box-header"><?= $escHtml($t('admin.menu.terms')) ?></div>
                <div class="p-3">
                    <div
                        class="tag-picker"
                        data-tag-picker
                        data-search-endpoint="<?= $escHtml($url('admin/api/v1/terms/search')) ?>"
                        data-initial="<?= $termsJson ?>"
                    >
                        <div class="tag-picker-field">
                            <div class="tag-picker-chips" data-tag-picker-chips></div>
                            <input class="tag-picker-input" type="text" data-tag-picker-input placeholder="<?= $escHtml($t('content.find_or_add_tag')) ?>">
                        </div>
                        <div class="tag-picker-suggestions" data-tag-picker-suggestions></div>
                        <input type="hidden" name="terms" value="<?= $escHtml($termsValue) ?>" data-tag-picker-value>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="content-box-header"><?= $escHtml($t('content.thumbnail')) ?></div>
                <div class="p-3">
                    <button
                        class="content-thumbnail-trigger mb-3<?= $thumbnailUrl === '' ? ' empty' : '' ?>"
                        type="button"
                        data-media-library-open
                        data-media-library-endpoint="<?= $escHtml($url('admin/api/v1/content/' . $contentId . '/media')) ?>"
                        data-media-base-url="<?= $escHtml($url('')) ?>"
                        data-current-media-id="<?= (int)($item['thumbnail'] ?? 0) ?>"
                    >
                        <?php if ($thumbnailUrl !== ''): ?>
                            <div class="content-thumbnail-preview">
                                <img src="<?= $escUrl($thumbnailUrl) ?>" alt="<?= $escHtml((string)($item['thumbnail_name'] ?? '')) ?>">
                            </div>
                        <?php else: ?>
                            <span><?= $escHtml($t('content.choose_image')) ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </aside>
    </div>
    <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= $escHtml((string)$errors['status']) ?></small><?php endif; ?>
</form>
<form
    id="content-delete-form"
    method="post"
    action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/delete')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>
</form>
<div class="media-library-modal modal-overlay" data-media-library-modal data-modal data-media-library-per-page="<?= \App\Service\Support\PaginationConfig::perPage() ?>">
    <div class="media-library-modal-dialog modal">
        <div class="media-library-modal-header">
            <strong>Media library</strong>
            <button class="btn btn-light btn-icon" type="button" data-modal-close aria-label="<?= $escHtml($t('common.close')) ?>">
                <?= $icon('cancel') ?>
            </button>
        </div>
        <div class="media-library-modal-layout">
            <div class="media-library-detail">
                <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                <div class="media-library-detail-meta">
                    <div>
                        <label><?= $escHtml($t('common.name')) ?></label>
                        <div class="d-flex gap-2">
                            <input type="text" value="" data-media-library-detail-name-input>
                            <button class="btn btn-light" type="button" data-media-library-rename disabled><?= $escHtml($t('common.save')) ?></button>
                        </div>
                    </div>
                </div>
                <small class="text-muted" data-media-library-status></small>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="button" data-media-library-choose disabled><?= $escHtml($t('content.choose')) ?></button>
                    <button
                        class="btn btn-danger"
                        type="button"
                        data-media-library-delete-open
                        data-modal-open
                        data-modal-target="#media-library-delete-modal"
                        data-type="<?= $escHtml($t('content.image')) ?>"
                        disabled
                    >
                        <?= $escHtml($t('common.delete')) ?>
                    </button>
                </div>
            </div>
            <div class="media-library-list">
                <form class="media-library-search" data-media-library-search>
                    <div class="search-field field-with-icon">
                        <input class="search-input" type="search" name="q" placeholder="<?= $escHtml($t('content.search_image')) ?>">
                        <span class="field-overlay field-overlay-end field-icon field-icon-soft" aria-hidden="true"><?= $icon('search') ?></span>
                    </div>
                </form>
                <form class="media-library-upload" method="post" enctype="multipart/form-data" action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/media/upload')) ?>" data-media-library-upload-form>
                    <?= $csrfField() ?>
                    <input type="hidden" name="content_id" value="<?= $contentId ?>">
                    <div class="custom-upload-field" data-media-library-upload-field>
                        <label class="btn btn-light custom-upload-button" for="content-thumbnail-upload">
                            <span class="custom-upload-main-icon" data-custom-upload-icon><?= $icon('upload') ?></span>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= $escHtml($t('common.upload_add_files')) ?>"><?= $escHtml($t('common.upload_add_files')) ?></span>
                            <span class="custom-upload-spinner" data-custom-upload-spinner aria-hidden="true"><?= $icon('loader') ?></span>
                        </label>
                        <input id="content-thumbnail-upload" type="file" name="thumbnail" accept="<?= $escHtml((string)($imageUploadAccept ?? '')) ?>" required>
                    </div>
                    <small class="text-muted d-block mt-2"><?= $escHtml(sprintf($t('common.allowed_upload_types'), (string)($imageUploadTypesLabel ?? ''))) ?></small>
                </form>
                <div class="media-library-grid" data-media-library-grid></div>
                <div class="pagination pagination-centered">
                    <a class="pagination-link disabled" href="#" data-media-library-prev aria-disabled="true" tabindex="-1"><?= $icon('prev') ?><span><?= $escHtml($t('common.previous')) ?></span></a>
                    <a class="pagination-link disabled" href="#" data-media-library-next aria-disabled="true" tabindex="-1"><span><?= $escHtml($t('common.next')) ?></span><?= $icon('next') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/thumbnail/0/select')) ?>" data-media-library-select-form data-action-template="<?= $escHtml($url('admin/api/v1/content/' . $contentId . '/thumbnail/{mediaId}/select')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-media-id>
</form>
<form method="post" action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/media/0/delete')) ?>" id="media-library-delete-form" data-action-template="<?= $escHtml($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/delete')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-delete-media-id>
</form>
<form method="post" action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/thumbnail/detach')) ?>" data-media-library-detach-form>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
</form>
<form method="post" action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/media/0/rename')) ?>" data-media-library-rename-form data-action-template="<?= $escHtml($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/rename')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-rename-media-id>
    <input type="hidden" name="name" value="" data-media-library-rename-name>
</form>
<form method="post" action="<?= $escUrl($url('admin/api/v1/content/' . $contentId . '/media/0/attach')) ?>" data-media-library-attach-form data-action-template="<?= $escHtml($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/attach')) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-attach-media-id>
</form>
<div class="modal-overlay" data-modal id="media-library-delete-modal">
    <div class="modal">
        <p data-modal-text><?= $escHtml($t('content.delete_image_confirm')) ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= $escHtml($t('common.cancel')) ?></button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-modal-confirm-manual><?= $escHtml($t('common.confirm')) ?></button>
        </div>
    </div>
</div>
<div class="modal-overlay" data-content-leave-modal>
    <div class="modal">
        <p><?= $escHtml($t('content.leave_page_confirm')) ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-content-leave-cancel><?= $escHtml($t('common.cancel')) ?></button>
            <button class="btn btn-primary" type="button" data-content-leave-confirm><?= $escHtml($t('common.confirm')) ?></button>
        </div>
    </div>
</div>
<div class="modal-overlay" data-modal id="content-delete-modal">
    <div class="modal">
        <p data-modal-text><?= $escHtml($t('content.delete_confirm')) ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= $escHtml($t('common.cancel')) ?></button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="content-delete-form"><?= $escHtml($t('common.confirm')) ?></button>
        </div>
    </div>
</div>
