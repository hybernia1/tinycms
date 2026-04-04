<form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/terms/add') : $url('admin/terms/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <div class="card p-4" style="max-width:760px;">
        <div class="mb-3">
            <label>Název</label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Popis</label>
            <textarea name="body" rows="6"><?= htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Uložit</button>
            <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/terms'), ENT_QUOTES, 'UTF-8') ?>">Zpět</a>
        </div>
    </div>
</form>
