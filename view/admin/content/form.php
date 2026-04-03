<?php
$createdRaw = trim((string)($item['created'] ?? ''));
$createdStamp = $createdRaw !== '' ? strtotime($createdRaw) : false;
$createdAt = $createdStamp !== false ? date('Y-m-d\\TH:i', $createdStamp) : '';
?>
<form class="content-editor-form" method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/content/add') : $url('admin/content/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
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
        </aside>
    </div>
</form>
