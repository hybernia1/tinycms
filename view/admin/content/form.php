<?php $createdAt = $formatInputDateTime((string)($item['created'] ?? '')); ?>
<?php
$initialTerms = array_values(array_filter(array_map(static fn($term): string => trim((string)$term), (array)($selectedTerms ?? []))));
$termsValue = implode(', ', $initialTerms);
$termsJson = htmlspecialchars(json_encode($initialTerms, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]', ENT_QUOTES, 'UTF-8');
?>
<?php
$thumbnailPath = trim((string)($item['thumbnail_path_webp'] ?? ''));
if ($thumbnailPath === '') {
    $thumbnailPath = trim((string)($item['thumbnail_path'] ?? ''));
}
$thumbnailUrl = $thumbnailPath !== '' ? $url($thumbnailPath) : '';
$contentId = (int)($item['id'] ?? 0);
$authUser = $_SESSION['auth'] ?? [];
$isEditor = (string)($authUser['role'] ?? '') === 'editor';
$currentUserId = (int)($authUser['id'] ?? 0);
?>
<form
    class="content-editor-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= htmlspecialchars($mode === 'add' ? $url('admin/content/add') : $url('admin/content/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"
    data-autosave-endpoint="<?= htmlspecialchars($url('admin/api/v1/content/autosave'), ENT_QUOTES, 'UTF-8') ?>"
    data-draft-init-endpoint="<?= htmlspecialchars($url('admin/api/v1/content/draft/init'), ENT_QUOTES, 'UTF-8') ?>"
    data-edit-url-base="<?= htmlspecialchars($url('admin/content/edit?id='), ENT_QUOTES, 'UTF-8') ?>"
>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>" data-content-id-hidden>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3"><?= htmlspecialchars((string)($item['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php if (!empty($errors['excerpt'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['excerpt'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
            <div class="m-0">
                <label><?= htmlspecialchars($t('content.body', 'Content'), ENT_QUOTES, 'UTF-8') ?></label>
                <textarea
                    name="body"
                    rows="14"
                    data-wysiwyg
                    data-content-id="<?= $contentId ?>"
                    data-media-library-endpoint="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media'), ENT_QUOTES, 'UTF-8') ?>"
                    data-media-base-url="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"
                    data-link-title-endpoint="<?= htmlspecialchars($url('admin/api/v1/link-title'), ENT_QUOTES, 'UTF-8') ?>"
                ><?= htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
        <aside class="content-editor-sidebar">
            <div class="card">
                <div class="content-box-header"><?= htmlspecialchars($t('content.publication', 'Publication'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <div class="mb-3">
                        <label><?= htmlspecialchars($t('content.status', 'Status'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="status">
                            <?php foreach ($availableStatuses as $statusValue): ?>
                                <option value="<?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= (string)($item['status'] ?? 'draft') === (string)$statusValue ? 'selected' : '' ?>><?= htmlspecialchars($t('content.statuses.' . (string)$statusValue, (string)$statusValue), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['status'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                    <div class="m-0">
                        <label><?= htmlspecialchars($t('content.publish_date', 'Publish date'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="datetime-local" name="created" value="<?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['created'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                </div>
                <div class="content-box-footer d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('common.save', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/content'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.back', 'Back'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if ($mode === 'edit'): ?>
                        <button class="btn btn-light" type="button" data-modal-open data-modal-target="#content-delete-modal"><?= htmlspecialchars($t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isEditor): ?>
                <input type="hidden" name="author" value="<?= $currentUserId > 0 ? $currentUserId : '' ?>">
            <?php else: ?>
                <div class="card">
                    <div class="content-box-header"><?= htmlspecialchars($t('common.author', 'Author'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="p-3">
                        <select name="author">
                            <option value=""><?= htmlspecialchars($t('common.no_author', 'No author'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($authors as $author): ?>
                                <?php $authorId = (int)($author['ID'] ?? 0); ?>
                                <option value="<?= $authorId ?>" <?= (int)($item['author'] ?? 0) === $authorId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($author['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($author['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['author'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="content-box-header"><?= htmlspecialchars($t('admin.menu.terms', 'Tags'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted"><?= htmlspecialchars($t('content.save_to_assign_tags', 'Save content first to assign tags.'), ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                    <div
                        class="tag-picker"
                        data-tag-picker
                        data-suggest-endpoint="<?= htmlspecialchars($url('admin/api/v1/terms/suggest'), ENT_QUOTES, 'UTF-8') ?>"
                        data-initial="<?= $termsJson ?>"
                    >
                        <div class="tag-picker-field">
                            <div class="tag-picker-chips" data-tag-picker-chips></div>
                            <input class="tag-picker-input" type="text" data-tag-picker-input placeholder="<?= htmlspecialchars($t('content.find_or_add_tag', 'Find or add tag'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="tag-picker-suggestions" data-tag-picker-suggestions></div>
                        <input type="hidden" name="terms" value="<?= htmlspecialchars($termsValue, ENT_QUOTES, 'UTF-8') ?>" data-tag-picker-value>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="content-box-header"><?= htmlspecialchars($t('content.thumbnail', 'Thumbnail'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted"><?= htmlspecialchars($t('content.thumbnail_hint', 'Thumbnail can be selected before manual save.'), ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                    <button
                        class="content-thumbnail-trigger mb-3<?= $thumbnailUrl === '' ? ' empty' : '' ?>"
                        type="button"
                        data-media-library-open
                        data-media-library-endpoint="<?= htmlspecialchars($url('admin/api/v1/content/' . $contentId . '/media'), ENT_QUOTES, 'UTF-8') ?>"
                        data-media-base-url="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"
                        data-current-media-id="<?= (int)($item['thumbnail'] ?? 0) ?>"
                    >
                        <?php if ($thumbnailUrl !== ''): ?>
                            <div class="content-thumbnail-preview">
                                <img src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['thumbnail_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        <?php else: ?>
                            <span><?= htmlspecialchars($t('content.choose_image', 'Choose image'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </button>
                    <?php if ((int)($item['thumbnail'] ?? 0) > 0): ?>
                        <div class="mt-2 d-flex gap-2" data-media-library-detach-wrap>
                            <button class="btn btn-light" type="button" data-media-library-detach><?= htmlspecialchars($t('content.detach', 'Detach'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</form>
<?php if ($mode === 'edit'): ?>
<form id="content-delete-form" method="post" action="<?= htmlspecialchars($url('admin/content/edit/delete?id=' . $contentId), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
</form>
<?php endif; ?>
<div class="media-library-modal" data-media-library-modal>
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
                    <div><strong><?= htmlspecialchars($t('content.path', 'Path'), ENT_QUOTES, 'UTF-8') ?>:</strong> <span data-media-library-detail-path>—</span></div>
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
                <div class="media-library-pagination">
                    <button class="btn btn-light" type="button" data-media-library-prev><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></button>
                    <span data-media-library-page>1 / 1</span>
                    <button class="btn btn-light" type="button" data-media-library-next><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></button>
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
<div class="modal-overlay" data-modal id="media-library-delete-modal">
    <div class="modal">
        <p data-modal-text><?= htmlspecialchars($t('content.delete_image_confirm', 'Do you really want to delete this image?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-media-library-delete-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
<div class="modal-overlay" data-content-leave-modal>
    <div class="modal">
        <p><?= htmlspecialchars($t('content.leave_page_confirm', 'Neuložené změny budou ztraceny. Opravdu chcete pokračovat?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-content-leave-cancel><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-content-leave-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
<?php if ($mode === 'edit'): ?>
<div class="modal-overlay" data-modal id="content-delete-modal">
    <div class="modal">
        <p data-modal-text><?= htmlspecialchars($t('content.delete_confirm', 'Do you really want to delete this content?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="content-delete-form"><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
<?php endif; ?>
