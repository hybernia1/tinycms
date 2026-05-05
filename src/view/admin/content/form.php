<?php
if (!defined('BASE_DIR')) {
    exit;
}
 $createdAt = $formatInputDateTime((string)($item['created'] ?? '')); ?>
<?php
$initialTerms = array_values(array_filter(array_map(static fn($term): string => trim((string)$term), (array)($selectedTerms ?? []))));
$termsValue = implode(', ', $initialTerms);
$termsJson = esc_attr(esc_json($initialTerms));
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
    action="<?= esc_url($mode === 'add' ? $url('admin/api/v1/content/add') : $url('admin/api/v1/content/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    <?= $mode === 'edit' ? 'data-stay-on-page' : '' ?>
    data-autosave-endpoint="<?= esc_attr($url('admin/api/v1/content/autosave')) ?>"
    data-draft-init-endpoint="<?= esc_attr($url('admin/api/v1/content/draft/init')) ?>"
    data-edit-url-base="<?= esc_attr($url('admin/content/edit?id=')) ?>"
>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>" data-content-id-hidden>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label><?= esc_html(t('common.name')) ?></label>
                <input type="text" name="name" value="<?= esc_attr((string)($item['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= esc_html((string)$errors['name']) ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3"><?= esc_html((string)($item['excerpt'] ?? '')) ?></textarea>
                <?php if (!empty($errors['excerpt'])): ?><small class="text-danger"><?= esc_html((string)$errors['excerpt']) ?></small><?php endif; ?>
            </div>
            <input type="hidden" name="status" value="<?= esc_attr((string)($item['status'] ?? 'draft')) ?>" data-content-status-hidden>
            <div class="m-0">
                <textarea
                    name="body"
                    rows="14"
                    data-wysiwyg
                    data-content-id="<?= $contentId ?>"
                    data-media-library-endpoint="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/media')) ?>"
                    data-media-base-url="<?= esc_attr($url('')) ?>"
                    data-link-title-endpoint="<?= esc_attr($url('admin/api/v1/content/link-title')) ?>"
                ><?= esc_html((string)($item['body'] ?? '')) ?></textarea>
            </div>
        </div>
        <aside class="content-editor-sidebar">
            <div class="card">
                <div class="content-box-header content-box-header-actions">
                    <span><?= esc_html(t('content.publication')) ?></span>
                    <a
                        class="btn btn-light"
                        href="<?= esc_url($previewUrl) ?>"
                        target="<?= esc_attr($previewTarget) ?>"
                        data-content-preview-link
                        <?= $previewHidden ? 'hidden' : '' ?>
                    >
                        <?= esc_html(t('content.preview')) ?>
                    </a>
                </div>
                <div class="p-3">
                    <div class="mb-3">
                        <label><?= esc_html(t('content.publish_date')) ?></label>
                        <input type="datetime-local" name="created" value="<?= esc_attr($createdAt) ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= esc_html((string)$errors['created']) ?></small><?php endif; ?>
                    </div>
                    <div class="mt-3 mb-3">
                        <label><?= esc_html(t('content.type')) ?></label>
                        <?php $selectedType = (string)($item['type'] ?? \App\Service\Application\Content::TYPE_ARTICLE); ?>
                        <select name="type" required>
                            <?php foreach ((array)($contentTypes ?? []) as $typeOption): ?>
                                <?php $typeKey = (string)$typeOption; ?>
                                <option value="<?= esc_attr($typeKey) ?>" <?= $selectedType === $typeKey ? 'selected' : '' ?>>
                                    <?= esc_html(t('content.types.' . $typeKey, ucfirst(str_replace('_', ' ', $typeKey)))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['type'])): ?><small class="text-danger"><?= esc_html((string)$errors['type']) ?></small><?php endif; ?>
                    </div>
                    <div class="mt-3 mb-3">
                        <input type="hidden" name="comments_enabled" value="0">
                        <label>
                            <input type="checkbox" name="comments_enabled" value="1" <?= (int)($item['comments_enabled'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <span><?= esc_html(t('content.comments_enabled')) ?></span>
                        </label>
                    </div>
                    <div class="mt-3 mb-3">
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

            <div class="card">
                <div class="content-box-header"><?= esc_html(t('admin.menu.terms')) ?></div>
                <div class="p-3">
                    <div
                        class="tag-picker"
                        data-picker
                        data-picker-mode="multi"
                        data-search-endpoint="<?= esc_attr($url('admin/api/v1/terms/search')) ?>"
                        data-initial="<?= $termsJson ?>"
                    >
                        <div class="tag-picker-field">
                            <div class="tag-picker-chips" data-picker-chips></div>
                            <input class="tag-picker-input" type="text" data-picker-input placeholder="<?= esc_attr(t('content.find_or_add_tag')) ?>">
                        </div>
                        <div class="tag-picker-suggestions" data-picker-suggestions></div>
                        <input type="hidden" name="terms" value="<?= esc_attr($termsValue) ?>" data-picker-value>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="content-box-header"><?= esc_html(t('content.thumbnail')) ?></div>
                <div class="p-3">
                    <button
                        class="media-picker-trigger mb-3<?= $thumbnailUrl === '' ? ' empty' : '' ?>"
                        type="button"
                        data-media-library-open
                        data-media-library-endpoint="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/media')) ?>"
                        data-media-base-url="<?= esc_attr($url('')) ?>"
                        data-current-media-id="<?= (int)($item['thumbnail'] ?? 0) ?>"
                        data-media-library-per-page="<?= defined('APP_POSTS_PER_PAGE') ? (int)APP_POSTS_PER_PAGE : 10 ?>"
                        data-media-upload-endpoint="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/media/upload')) ?>"
                        data-media-upload-accept="<?= esc_attr((string)($imageUploadAccept ?? '')) ?>"
                        data-media-select-template="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/thumbnail/{mediaId}/select')) ?>"
                        data-media-delete-template="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/delete')) ?>"
                        data-media-rename-template="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/rename')) ?>"
                        data-media-attach-template="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/media/{mediaId}/attach')) ?>"
                        data-media-detach-endpoint="<?= esc_attr($url('admin/api/v1/content/' . $contentId . '/thumbnail/detach')) ?>"
                    >
                        <?php if ($thumbnailUrl !== ''): ?>
                            <div class="media-picker-preview">
                                <img src="<?= esc_url($thumbnailUrl) ?>" alt="<?= esc_attr((string)($item['thumbnail_name'] ?? '')) ?>">
                            </div>
                        <?php else: ?>
                            <span><?= esc_html(t('content.choose_image')) ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </aside>
    </div>
    <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= esc_html((string)$errors['status']) ?></small><?php endif; ?>
</form>
<form
    id="content-delete-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/content/' . $contentId . '/delete')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>
</form>
