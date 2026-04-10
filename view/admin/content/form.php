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
    id="content-editor-form"
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
                <label><?= htmlspecialchars($t('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3"><?= htmlspecialchars((string)($item['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php if (!empty($errors['excerpt'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['excerpt'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
            <input type="hidden" name="status" value="<?= htmlspecialchars((string)($item['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8') ?>" data-content-status-hidden>
            <div class="m-0">
                <label><?= htmlspecialchars($t('content.body'), ENT_QUOTES, 'UTF-8') ?></label>
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
                <div class="content-box-header"><?= htmlspecialchars($t('content.publication'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <div class="m-0">
                        <label><?= htmlspecialchars($t('content.publish_date'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="datetime-local" name="created" value="<?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['created'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($isEditor): ?>
                <input type="hidden" name="author" value="<?= $currentUserId > 0 ? $currentUserId : '' ?>">
            <?php else: ?>
                <div class="card">
                    <div class="content-box-header"><?= htmlspecialchars($t('common.author'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="p-3">
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
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="content-box-header"><?= htmlspecialchars($t('admin.menu.terms'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted"><?= htmlspecialchars($t('content.save_to_assign_tags'), ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                    <div
                        class="tag-picker"
                        data-tag-picker
                        data-suggest-endpoint="<?= htmlspecialchars($url('admin/api/v1/terms/suggest'), ENT_QUOTES, 'UTF-8') ?>"
                        data-initial="<?= $termsJson ?>"
                    >
                        <div class="tag-picker-field">
                            <div class="tag-picker-chips" data-tag-picker-chips></div>
                            <input class="tag-picker-input" type="text" data-tag-picker-input placeholder="<?= htmlspecialchars($t('content.find_or_add_tag'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="tag-picker-suggestions" data-tag-picker-suggestions></div>
                        <input type="hidden" name="terms" value="<?= htmlspecialchars($termsValue, ENT_QUOTES, 'UTF-8') ?>" data-tag-picker-value>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="content-box-header"><?= htmlspecialchars($t('content.thumbnail'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="p-3">
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted"><?= htmlspecialchars($t('content.thumbnail_hint'), ENT_QUOTES, 'UTF-8') ?></small>
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
                            <span><?= htmlspecialchars($t('content.choose_image'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </button>
                    <?php if ((int)($item['thumbnail'] ?? 0) > 0): ?>
                        <div class="mt-2 d-flex gap-2" data-media-library-detach-wrap>
                            <button class="btn btn-light" type="button" data-media-library-detach><?= htmlspecialchars($t('content.detach'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
    <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['status'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    <?php if ($mode === 'edit'): ?>
        <button class="d-none" type="button" data-content-delete-trigger data-modal-open data-modal-target="#content-delete-modal"></button>
    <?php endif; ?>
</form>
<?php if ($mode === 'edit'): ?>
<form id="content-delete-form" method="post" action="<?= htmlspecialchars($url('admin/content/edit/delete?id=' . $contentId), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../modals/media-library-modal.php'; ?>
<?php require __DIR__ . '/../modals/content-leave-modal.php'; ?>
<?php if ($mode === 'edit'): ?>
<?php
$confirmModal = [
    'id' => 'content-delete-modal',
    'text' => $t('content.delete_confirm'),
    'overlay_attrs' => ['data-modal' => true],
    'cancel_attrs' => ['data-modal-close' => true],
    'confirm_attrs' => [
        'data-modal-confirm' => true,
        'data-form-id' => 'content-delete-form',
    ],
];
require __DIR__ . '/../modals/confirm-modal.php';
?>
<?php endif; ?>
