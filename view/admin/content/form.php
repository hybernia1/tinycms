<?php
$type = (string)($contentType['type'] ?? 'post');
$createdRaw = trim((string)($item['created'] ?? ''));
$createdAt = $dateTime->toInputValue($createdRaw);
?>
<form class="content-editor-form" method="post" action="<?= $escape($mode === 'add' ? $url('admin/content/add?type=' . urlencode($type)) : $url('admin/content/edit?id=' . (int)($item['id'] ?? 0) . '&type=' . urlencode($type))) ?>">
    <?= $csrfField() ?>
    <input type="hidden" name="type" value="<?= $escape($type) ?>">
    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="mb-3">
                <label>Název</label>
                <input type="text" name="name" value="<?= $escape((string)($item['name'] ?? '')) ?>" required>
                <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $escape((string)$errors['name']) ?></small><?php endif; ?>
            </div>
            <div class="mb-3">
                <label>Excerpt</label>
                <textarea name="excerpt" rows="3"><?= $escape((string)($item['excerpt'] ?? '')) ?></textarea>
            </div>
            <div class="m-0">
                <label>Obsah</label>
                <textarea name="body" rows="14"><?= $escape((string)($item['body'] ?? '')) ?></textarea>
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
                                <option value="<?= $escape((string)$statusValue) ?>" <?= (string)($item['status'] ?? 'draft') === (string)$statusValue ? 'selected' : '' ?>><?= $escape((string)$statusValue) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= $escape((string)$errors['status']) ?></small><?php endif; ?>
                    </div>
                    <div class="m-0">
                        <label>Publish date</label>
                        <input type="datetime-local" name="created" value="<?= $escape($createdAt) ?>">
                        <?php if (!empty($errors['created'])): ?><small class="text-danger"><?= $escape((string)$errors['created']) ?></small><?php endif; ?>
                    </div>
                </div>
                <div class="content-box-footer d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Uložit</button>
                    <a class="btn btn-light" href="<?= $escape($url('admin/content?type=' . urlencode($type))) ?>">Zpět</a>
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
                                <?= $escape((string)($author['name'] ?? '')) ?> (<?= $escape((string)($author['email'] ?? '')) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['author'])): ?><small class="text-danger"><?= $escape((string)$errors['author']) ?></small><?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</form>
