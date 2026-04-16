<form
    id="terms-form"
    method="post"
    action="<?= $e($mode === 'add' ? $url('admin/api/v1/terms/add') : $url('admin/api/v1/terms/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    <?= $mode === 'add' ? 'data-redirect-url="' . $e($url('admin/terms')) . '"' : 'data-stay-on-page' ?>
>
    <?= $csrfField() ?>
    <div class="card p-4">
        <div class="mb-3">
            <label><?= $e($t('common.name')) ?></label>
            <input type="text" name="name" value="<?= $e((string)($item['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $e((string)$errors['name']) ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit'): ?>
            <h3 class="mb-3"><?= $e($t('terms.used_in')) ?></h3>
            <?php if (($usages ?? []) === []): ?>
                <p class="text-muted m-0"><?= $e($t('terms.no_usage')) ?></p>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?= $e($t('content.post')) ?></th>
                            <th><?= $e($t('common.created')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usages as $usage): ?>
                            <tr>
                                <td>
                                    <a href="<?= $e($adminVars['entityEdit']('content', (int)($usage['id'] ?? 0))) ?>">
                                        <?= $e((string)($usage['name'] ?? '')) ?>
                                    </a>
                                </td>
                                <td><?= $e($formatDateTime((string)($usage['created'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>
