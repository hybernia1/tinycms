<div class="card p-5">
    <?php
    $previewPath = trim((string)($item['path_webp'] ?? ''));
    if ($previewPath === '') {
        $previewPath = trim((string)($item['path'] ?? ''));
    }
    $previewUrl = $previewPath !== '' ? $url($previewPath) : '';
    ?>
    <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/media/add') : $url('admin/media/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
        <div class="mb-3">
            <label>Název</label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Soubor <?= $mode === 'add' ? '' : '(volitelné, nahradí stávající)' ?></label>
            <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" <?= $mode === 'add' ? 'required' : '' ?>>
            <?php if (!empty($errors['file'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['file'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit'): ?>
            <div class="mb-3">
                <label>Path</label>
                <div class="text-muted"><?= htmlspecialchars((string)($item['path'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="mb-3">
                <label>Path webp</label>
                <div class="text-muted"><?= htmlspecialchars((string)($item['path_webp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php if ($previewUrl !== ''): ?>
                <div class="content-thumbnail-preview mb-3">
                    <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
