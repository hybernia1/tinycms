<div class="card p-5">
    <form method="post" action="<?= htmlspecialchars($url('admin/settings'), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>

        <?php foreach ($fields as $fieldKey => $field):
            $fieldType = (string)($field['type'] ?? 'text');
            $fieldValue = (string)($values[$fieldKey] ?? '');
            $labelKey = (string)($field['label_key'] ?? ('settings.fields.' . $fieldKey));
            $smtpClass = str_starts_with((string)$fieldKey, 'smtp_') ? ' js-smtp-field' : '';
        ?>
            <div class="mb-3<?= $smtpClass ?>">
                <label><?= htmlspecialchars($t($labelKey, (string)$fieldKey), ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($fieldType === 'textarea'): ?>
                    <textarea name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" rows="4"><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php elseif ($fieldType === 'select'): ?>
                    <?php $options = (array)($field['options'] ?? []); ?>
                    <select name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" <?= $fieldKey === 'mail_driver' ? 'data-mail-driver' : '' ?>>
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <?php $value = is_string($optionValue) ? trim($optionValue) : trim((string)$optionLabel); ?>
                            <?php if ($value === '' && (string)$optionValue !== '0') { continue; } ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldValue === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= $fieldKey === 'smtp_pass' ? 'password' : 'text' ?>" name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('settings.save', 'Save settings'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
<script>
(function () {
    const driver = document.querySelector('[data-mail-driver]');
    if (!driver) return;
    const smtpFields = document.querySelectorAll('.js-smtp-field');
    const toggle = () => {
        const visible = driver.value === 'smtp';
        smtpFields.forEach((el) => {
            el.style.display = visible ? '' : 'none';
        });
    };
    driver.addEventListener('change', toggle);
    toggle();
})();
</script>
