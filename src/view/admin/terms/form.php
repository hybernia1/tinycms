<form
    id="terms-form"
    method="post"
    action="<?= esc_url($mode === 'add' ? $url('admin/api/v1/terms/add') : $url('admin/api/v1/terms/' . (int)($item['id'] ?? 0) . '/edit')) ?>"
    data-api-submit
    <?= $mode === 'edit' ? 'data-stay-on-page' : '' ?>
>
    <?= $csrfField() ?>
    <div class="card p-4">
        <div class="mb-3">
            <label><?= esc_html(t('common.name')) ?></label>
            <input type="text" name="name" value="<?= esc_attr((string)($item['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= esc_html((string)$errors['name']) ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit'): ?>
            <h3 class="mb-3"><?= esc_html(t('terms.used_in')) ?></h3>
            <?php if (($usages ?? []) === []): ?>
                <p class="text-muted m-0"><?= esc_html(t('terms.no_usage')) ?></p>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?= esc_html(t('content.post')) ?></th>
                            <th><?= esc_html(t('common.created')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usages as $usage): ?>
                            <tr>
                                <td>
                                    <a href="<?= esc_url($url('admin/content/edit?id=' . (int)($usage['id'] ?? 0))) ?>">
                                        <?= esc_html((string)($usage['name'] ?? '')) ?>
                                    </a>
                                </td>
                                <td><?= esc_html($formatDateTime((string)($usage['created'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>
