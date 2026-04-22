<form
    id="terms-form"
    method="post"
    action="<?= $escUrl($mode === 'add' ? $url('admin/api/v1/terms/add') : $url('admin/api/v1/terms/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    <?= $mode === 'edit' ? 'data-stay-on-page' : '' ?>
>
    <?= $csrfField() ?>
    <div class="card p-4">
        <div class="mb-3">
            <label><?= $escHtml($t('common.name')) ?></label>
            <input type="text" name="name" value="<?= $escHtml((string)($item['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $escHtml((string)$errors['name']) ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit'): ?>
            <h3 class="mb-3"><?= $escHtml($t('terms.used_in')) ?></h3>
            <?php if (($usages ?? []) === []): ?>
                <p class="text-muted m-0"><?= $escHtml($t('terms.no_usage')) ?></p>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?= $escHtml($t('content.post')) ?></th>
                            <th><?= $escHtml($t('common.created')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usages as $usage): ?>
                            <tr>
                                <td>
                                    <a href="<?= $escUrl($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0))) ?>">
                                        <?= $escHtml((string)($usage['name'] ?? '')) ?>
                                    </a>
                                </td>
                                <td><?= $escHtml($formatDateTime((string)($usage['created'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>
