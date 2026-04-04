<?php
$createdRaw = trim((string)($item['created'] ?? ''));
$createdStamp = $createdRaw !== '' ? strtotime($createdRaw) : false;
$createdAt = $createdStamp !== false ? date('Y-m-d\\TH:i', $createdStamp) : '';
?>
<?php
$thumbnailPath = trim((string)($item['thumbnail_path_webp'] ?? ''));
if ($thumbnailPath === '') {
    $thumbnailPath = trim((string)($item['thumbnail_path'] ?? ''));
}
$thumbnailUrl = $thumbnailPath !== '' ? $url($thumbnailPath) : '';
$contentId = (int)($item['id'] ?? 0);
?>
<form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/content/add') : $url('admin/content/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label>Název</label>
                <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3"><?= htmlspecialchars((string)($item['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="m-0">
                <label>Obsah</label>
                <textarea name="body" rows="14" data-wysiwyg><?= htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
        <aside class="content-editor-sidebar">
            <div class="card">
                <div class="content-box-header">Publikace</div>
                <div class="p-3">
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status">
                            <?php foreach ($availableStatuses as $statusValue): ?>
                                <option value="<?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= (string)($item['status'] ?? 'draft') === (string)$statusValue ? 'selected' : '' ?>><?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['status'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                    <div class="m-0">
                        <label>Publish date</label>
                        <input type="datetime-local" name="created" value="<?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['created'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                </div>
                <div class="content-box-footer d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Uložit</button>
                    <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/content'), ENT_QUOTES, 'UTF-8') ?>">Zpět</a>
                </div>
            </div>
            <div class="card">
                <div class="content-box-header">Autor</div>
                <div class="p-3">
                    <label>Autor</label>
                    <select name="author">
                        <option value="">Bez autora</option>
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
            <div class="card">
                <div class="content-box-header">Thumbnail</div>
                <div class="p-3">
                    <?php if ($mode === 'add'): ?>
                        <small class="text-muted">Nejdřív uložte obsah, poté přidejte thumbnail.</small>
                    <?php else: ?>
                        <button
                            class="content-thumbnail-trigger mb-3<?= $thumbnailUrl === '' ? ' empty' : '' ?>"
                            type="button"
                            data-media-library-open
                            data-media-library-endpoint="<?= htmlspecialchars($url('admin/content/media-library'), ENT_QUOTES, 'UTF-8') ?>"
                            data-media-base-url="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"
                            data-current-media-id="<?= (int)($item['thumbnail'] ?? 0) ?>"
                        >
                            <?php if ($thumbnailUrl !== ''): ?>
                                <div class="content-thumbnail-preview">
                                    <img src="<?= htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['thumbnail_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            <?php else: ?>
                                <span>Zvolit obrázek</span>
                            <?php endif; ?>
                        </button>
                        <?php if ((int)($item['thumbnail'] ?? 0) > 0): ?>
                            <div class="mt-2 d-flex gap-2">
                                <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>">
                                <button class="btn btn-light" type="submit" formaction="<?= htmlspecialchars($url('admin/content/thumbnail/detach'), ENT_QUOTES, 'UTF-8') ?>" formmethod="post" formnovalidate>Odpojit</button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</form>

<?php if ($mode !== 'add'): ?>
<div class="media-library-modal" data-media-library-modal>
    <div class="media-library-modal-dialog">
        <div class="media-library-modal-header">
            <strong>Media library</strong>
            <button class="btn btn-light btn-icon" type="button" data-media-library-close aria-label="Zavřít">
                <?= $icon('cancel') ?>
            </button>
        </div>
        <div class="media-library-modal-layout">
            <div class="media-library-detail">
                <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                <div class="media-library-detail-meta">
                    <div>
                        <label>Název</label>
                        <div class="d-flex gap-2">
                            <input type="text" value="" data-media-library-detail-name-input>
                            <button class="btn btn-light" type="button" data-media-library-rename disabled>Uložit</button>
                        </div>
                    </div>
                    <div><strong>Cesta:</strong> <span data-media-library-detail-path>—</span></div>
                    <div><strong>Vytvořeno:</strong> <span data-media-library-detail-created>—</span></div>
                </div>
                <small class="text-muted" data-media-library-status></small>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="button" data-media-library-choose disabled>Zvolit</button>
                    <button
                        class="btn btn-danger"
                        type="button"
                        data-media-library-delete-open
                        data-modal-open
                        data-modal-target="#media-library-delete-modal"
                        data-type="obrázek"
                        data-form-id="media-library-delete-form"
                        disabled
                    >
                        Smazat
                    </button>
                </div>
            </div>
            <div class="media-library-list">
                <form class="media-library-search" data-media-library-search>
                    <div class="search-field">
                        <input class="search-input" type="search" name="q" placeholder="Hledat obrázek">
                        <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
                    </div>
                </form>
                <form class="media-library-upload" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($url('admin/content/media-library/upload'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-upload-form>
                    <?= $csrfField() ?>
                    <input type="hidden" name="content_id" value="<?= $contentId ?>">
                    <input type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                    <button class="btn btn-primary" type="submit" data-media-library-upload-button>
                        <span data-media-library-upload-label>Nahrát nový</span>
                    </button>
                </form>
                <div class="media-library-grid" data-media-library-grid></div>
                <div class="media-library-pagination">
                    <button class="btn btn-light" type="button" data-media-library-prev>Předchozí</button>
                    <span data-media-library-page>1 / 1</span>
                    <button class="btn btn-light" type="button" data-media-library-next>Další</button>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="<?= htmlspecialchars($url('admin/content/thumbnail/select'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-select-form>
    <?= $csrfField() ?>
    <input type="hidden" name="id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-media-id>
</form>
<form method="post" action="<?= htmlspecialchars($url('admin/content/media-library/delete'), ENT_QUOTES, 'UTF-8') ?>" id="media-library-delete-form">
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-delete-media-id>
</form>
<form method="post" action="<?= htmlspecialchars($url('admin/content/media-library/rename'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-rename-form>
    <?= $csrfField() ?>
    <input type="hidden" name="content_id" value="<?= $contentId ?>">
    <input type="hidden" name="media_id" value="" data-media-library-rename-media-id>
    <input type="hidden" name="name" value="" data-media-library-rename-name>
</form>
<div class="modal-overlay" data-modal id="media-library-delete-modal">
    <div class="modal">
        <p data-modal-text>Skutečně smazat tento obrázek?</p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close>Zrušit</button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="media-library-delete-form">Potvrdit</button>
        </div>
    </div>
</div>
<?php endif; ?>
