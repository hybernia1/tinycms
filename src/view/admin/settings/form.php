<div class="card p-4">
    <form
        id="settings-form"
        method="post"
        enctype="multipart/form-data"
        action="<?= $e($url('admin/api/v1/settings')) ?>"
        data-api-submit
        data-redirect-url="<?= $e($url('admin/settings')) ?>"
    >
        <?= $csrfField() ?>

        <?php foreach ($fields as $fieldKey => $field):
            $fieldType = (string)($field['type'] ?? 'text');
            $fieldValue = (string)($values[$fieldKey] ?? '');
            $labelKey = (string)($field['label_key'] ?? ('settings.fields.' . $fieldKey));
        ?>
            <div class="mb-3">
                <label><?= $e($t($labelKey, (string)$fieldKey)) ?></label>
                <?php if ($fieldType === 'textarea'): ?>
                    <textarea name="settings[<?= $e((string)$fieldKey) ?>]" rows="4"><?= $e($fieldValue) ?></textarea>
                <?php elseif ($fieldType === 'select'): ?>
                    <?php $options = (array)($field['options'] ?? []); ?>
                    <select name="settings[<?= $e((string)$fieldKey) ?>]">
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <?php $value = is_string($optionValue) ? trim($optionValue) : trim((string)$optionLabel); ?>
                            <?php if ($value === '') { continue; } ?>
                            <option value="<?= $e($value) ?>" <?= $fieldValue === $value ? 'selected' : '' ?>>
                                <?= $e((string)$optionLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($fieldType === 'file'): ?>
                    <?php $inputName = $fieldKey === 'logo' ? 'logo_file' : 'favicon_file'; ?>
                    <?php $fileInputId = 'settings-file-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)$fieldKey); ?>
                    <div class="custom-upload-field">
                        <label class="btn btn-light custom-upload-button" for="<?= $e($fileInputId) ?>">
                            <?= $icon('upload') ?>
                            <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= $e($t('common.upload_add_files')) ?>"><?= $e($t('common.upload_add_files')) ?></span>
                        </label>
                        <input id="<?= $e($fileInputId) ?>" type="file" name="<?= $e($inputName) ?>" accept="<?= $e((string)($siteImageUploadAccept ?? '')) ?>">
                    </div>
                    <small class="text-muted d-block mt-2"><?= $e(sprintf($t('common.allowed_upload_types'), (string)($siteImageUploadTypesLabel ?? ''))) ?></small>
                    <?php if ($fieldValue !== ''): ?>
                        <div class="mt-2">
                            <div class="text-muted"><?= $e($fieldValue) ?></div>
                            <img src="<?= $e($url($fieldValue)) ?>" alt="<?= $e((string)$fieldKey) ?> preview" style="width:32px;height:32px">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <input type="<?= $fieldType === 'password' ? 'password' : 'text' ?>" name="settings[<?= $e((string)$fieldKey) ?>]" value="<?= $e($fieldValue) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </form>
</div>
