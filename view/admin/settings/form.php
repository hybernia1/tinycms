<div class="card p-5">
    <h1 class="m-0 mb-4">Nastavení webu</h1>
    <form method="post" action="<?= htmlspecialchars($url('admin/settings'), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>

        <?php foreach ($groups as $groupKey => $group): ?>
            <div class="mb-4">
                <h2 class="m-0 mb-3"><?= htmlspecialchars((string)($group['label'] ?? $groupKey), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php foreach (($group['fields'] ?? []) as $fieldKey => $field):
                    $fieldType = (string)($field['type'] ?? 'text');
                    $fieldValue = (string)($values[$groupKey][$fieldKey] ?? '');
                ?>
                    <div class="mb-3">
                        <label><?= htmlspecialchars((string)($field['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8') ?></label>
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea name="settings[<?= htmlspecialchars((string)$groupKey, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" rows="4"><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?= htmlspecialchars((string)$groupKey, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <button class="btn btn-primary" type="submit">Uložit nastavení</button>
    </form>
</div>
