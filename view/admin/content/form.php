<?php
$type = (string)($contentType['type'] ?? 'post');
?>
<div class="card p-5">
    <h1 class="m-0 mb-4"><?= $mode === 'add' ? 'Přidat ' . htmlspecialchars((string)($contentType['label_singular'] ?? 'obsah'), ENT_QUOTES, 'UTF-8') : 'Upravit ' . htmlspecialchars((string)($contentType['label_singular'] ?? 'obsah'), ENT_QUOTES, 'UTF-8') ?></h1>
    <form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/content/add?type=' . urlencode($type)) : $url('admin/content/edit?id=' . (int)($item['id'] ?? 0) . '&type=' . urlencode($type)), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
        <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
            <label>Název</label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status">
                <?php foreach ($availableStatuses as $statusValue): ?>
                    <option value="<?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= (string)($item['status'] ?? 'draft') === (string)$statusValue ? 'selected' : '' ?>><?= htmlspecialchars((string)$statusValue, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['status'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['status'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Excerpt</label>
            <textarea name="excerpt" rows="3"><?= htmlspecialchars((string)($item['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="mb-4">
            <label>Obsah</label>
            <textarea name="body" rows="10"><?= htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Uložit</button>
        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/content?type=' . urlencode($type)), ENT_QUOTES, 'UTF-8') ?>">Zpět</a>
    </form>
</div>
