<form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/terms/add') : $url('admin/terms/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <div class="card p-5">
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.description'), ENT_QUOTES, 'UTF-8') ?></label>
            <textarea name="body" rows="6"><?= htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
            <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/terms'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.back'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</form>
