<form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/menu/add') : $url('admin/menu/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <div class="card p-4" style="max-width:760px;">
        <div class="mb-3">
            <label>Název</label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>

        <div class="mb-3">
            <label>Nadřazená položka</label>
            <select name="parent_id">
                <option value="">— žádná —</option>
                <?php foreach (($parentOptions ?? []) as $parent): ?>
                    <?php $parentId = (int)($parent['id'] ?? 0); ?>
                    <option value="<?= $parentId ?>" <?= (int)($item['parent_id'] ?? 0) === $parentId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($parent['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['parent_id'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['parent_id'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>

        <div class="mb-3">
            <label>Navázaný obsah</label>
            <select name="content_id">
                <option value="">— žádný —</option>
                <?php foreach (($contentOptions ?? []) as $content): ?>
                    <?php $contentId = (int)($content['id'] ?? 0); ?>
                    <option value="<?= $contentId ?>" <?= (int)($item['content_id'] ?? 0) === $contentId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($content['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['content_id'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['content_id'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>

        <div class="mb-3">
            <label>URL</label>
            <input type="text" name="url" value="<?= htmlspecialchars((string)($item['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="/kontakt nebo https://...">
        </div>

        <div class="mb-3">
            <label>Pozice</label>
            <input type="number" name="position" value="<?= (int)($item['position'] ?? 0) ?>" step="1">
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Uložit</button>
            <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/menu'), ENT_QUOTES, 'UTF-8') ?>">Zpět</a>
        </div>
    </div>
</form>
