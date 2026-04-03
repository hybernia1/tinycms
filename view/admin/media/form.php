<div class="card p-5">
    <form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/media/add') : $url('admin/media/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
        <div class="mb-3">
            <label>Název</label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Path</label>
            <input type="text" name="path" value="<?= htmlspecialchars((string)($item['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['path'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['path'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Path webp</label>
            <input type="text" name="path_webp" value="<?= htmlspecialchars((string)($item['path_webp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="mb-4">
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
        <button class="btn btn-primary" type="submit">Uložit</button>
        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/media'), ENT_QUOTES, 'UTF-8') ?>">Zpět</a>
    </form>
</div>
