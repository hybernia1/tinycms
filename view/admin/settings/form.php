<div class="card p-5">
    <form method="post" action="<?= htmlspecialchars($url('admin/settings'), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>

        <?php foreach ($fields as $fieldKey => $field):
            $fieldType = (string)($field['type'] ?? 'text');
            $fieldValue = (string)($values[$fieldKey] ?? '');
            $labelKey = (string)($field['label_key'] ?? ('settings.fields.' . $fieldKey));
        ?>
            <div class="mb-3">
                <label><?= htmlspecialchars($t($labelKey, (string)$fieldKey), ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($fieldType === 'textarea'): ?>
                    <textarea name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" rows="4"><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php elseif ($fieldType === 'select'): ?>
                    <?php $options = array_values(array_filter(array_map(static fn($value): string => trim((string)$value), (array)($field['options'] ?? [])))); ?>
                    <select name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]">
                        <?php foreach ($options as $option): ?>
                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldValue === $option ? 'selected' : '' ?>>
                                <?= htmlspecialchars(strtoupper($option), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('settings.save', 'Save settings'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
