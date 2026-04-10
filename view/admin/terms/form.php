<form id="terms-editor-form" method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/terms/add') : $url('admin/terms/edit?id=' . (int)($item['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
    <?= $csrfField() ?>
    <div class="card p-4">
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit'): ?>
            <h3 class="mb-3"><?= htmlspecialchars($t('terms.used_in'), ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (($usages ?? []) === []): ?>
                <p class="text-muted m-0"><?= htmlspecialchars($t('terms.no_usage'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?= htmlspecialchars($t('content.post'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars($t('common.created'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usages as $usage): ?>
                            <tr>
                                <td>
                                    <a href="<?= htmlspecialchars($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)($usage['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($formatDateTime((string)($usage['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>
