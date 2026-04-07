<div class="card p-5">
    <form method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($url('admin/settings'), ENT_QUOTES, 'UTF-8') ?>">
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
                    <?php $options = (array)($field['options'] ?? []); ?>
                    <select name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]">
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <?php $value = is_string($optionValue) ? trim($optionValue) : trim((string)$optionLabel); ?>
                            <?php if ($value === '') { continue; } ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldValue === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($fieldType === 'file'): ?>
                    <?php $inputName = $fieldKey === 'logo' ? 'logo_file' : 'favicon_file'; ?>
                    <?php $fileInputId = 'settings-file-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)$fieldKey); ?>
                    <div class="custom-upload-field">
                        <label class="btn btn-light custom-upload-button" for="<?= htmlspecialchars($fileInputId, ENT_QUOTES, 'UTF-8') ?>">
                            <?= $icon('upload') ?>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= htmlspecialchars($t('common.upload_add_files', 'Add files'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.upload_add_files', 'Add files'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                        <input id="<?= htmlspecialchars($fileInputId, ENT_QUOTES, 'UTF-8') ?>" type="file" name="<?= htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8') ?>" accept=".png,.ico,.svg,.jpg,.jpeg,.webp,.gif,image/png,image/x-icon,image/vnd.microsoft.icon,image/svg+xml,image/jpeg,image/webp,image/gif">
                    </div>
                    <?php if ($fieldValue !== ''): ?>
                        <div class="mt-2">
                            <div class="text-muted"><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></div>
                            <img src="<?= htmlspecialchars($url($fieldValue), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?> preview" style="width:32px;height:32px">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <input type="text" name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('settings.save', 'Save settings'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
