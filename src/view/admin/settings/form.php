<?php
$sections = [];
foreach ($fields as $fieldKey => $field) {
    $sectionKey = (string)($field['section'] ?? 'general');
    $sections[$sectionKey][$fieldKey] = $field;
}
$orderedSections = [];
foreach (['general', 'localization', 'content', 'media', 'appearance'] as $sectionKey) {
    if (isset($sections[$sectionKey])) {
        $orderedSections[$sectionKey] = $sections[$sectionKey];
        unset($sections[$sectionKey]);
    }
}
$sections = array_merge($orderedSections, $sections);
?>
<form
    id="settings-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= esc_url($url('admin/api/v1/settings')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>

    <nav class="filter-nav settings-tabs mb-3" data-settings-tabs>
        <?php foreach ($sections as $sectionKey => $sectionFields): ?>
            <button
                class="filter-link<?= $sectionKey === array_key_first($sections) ? ' active' : '' ?>"
                type="button"
                data-settings-tab="<?= esc_attr((string)$sectionKey) ?>"
            >
                <?= esc_html(t('settings.sections.' . $sectionKey, ucfirst((string)$sectionKey))) ?>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="card p-4">
        <?php foreach ($sections as $sectionKey => $sectionFields): ?>
            <div data-settings-tab-panel="<?= esc_attr((string)$sectionKey) ?>" <?= $sectionKey === array_key_first($sections) ? '' : 'hidden' ?>>
                <?php foreach ($sectionFields as $fieldKey => $field):
                    $fieldType = (string)($field['type'] ?? 'text');
                    $fieldValue = (string)($values[$fieldKey] ?? '');
                    $labelKey = (string)($field['label_key'] ?? ('settings.fields.' . $fieldKey));
                ?>
                    <div class="mb-3">
                        <label><?= esc_html(t($labelKey, (string)$fieldKey)) ?></label>
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea name="settings[<?= esc_attr((string)$fieldKey) ?>]" rows="4"><?= esc_html($fieldValue) ?></textarea>
                        <?php elseif ($fieldType === 'select'): ?>
                            <?php $options = (array)($field['options'] ?? []); ?>
                            <select name="settings[<?= esc_attr((string)$fieldKey) ?>]">
                                <?php foreach ($options as $optionValue => $optionLabel): ?>
                                    <?php $value = trim((string)$optionValue); ?>
                                    <option value="<?= esc_attr($value) ?>" <?= $fieldValue === $value ? 'selected' : '' ?>>
                                        <?= esc_html((string)$optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($fieldType === 'file'): ?>
                            <?php $inputName = $fieldKey === 'logo' ? 'logo_file' : 'favicon_file'; ?>
                            <?php $fileInputId = 'settings-file-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)$fieldKey); ?>
                            <div class="custom-upload-field">
                                <label class="btn btn-light custom-upload-button" for="<?= esc_attr($fileInputId) ?>">
                                    <?= icon('upload') ?>
                                    <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= esc_attr(t('common.upload_add_files')) ?>"><?= esc_html(t('common.upload_add_files')) ?></span>
                                </label>
                                <input id="<?= esc_attr($fileInputId) ?>" type="file" name="<?= esc_attr($inputName) ?>" accept="<?= esc_attr((string)($siteImageUploadAccept ?? '')) ?>">
                            </div>
                            <small class="text-muted d-block mt-2"><?= esc_html(sprintf(t('common.allowed_upload_types'), (string)($siteImageUploadTypesLabel ?? ''))) ?></small>
                            <?php if ($fieldValue !== ''): ?>
                                <div class="settings-file-preview">
                                    <div class="text-muted"><?= esc_html($fieldValue) ?></div>
                                    <img src="<?= esc_url($url($fieldValue)) ?>" alt="<?= esc_attr((string)$fieldKey) ?> preview">
                                </div>
                            <?php endif; ?>
                        <?php elseif ($fieldType === 'number'): ?>
                            <?php $min = (int)($field['min'] ?? 1); ?>
                            <?php $max = (int)($field['max'] ?? 100); ?>
                            <input type="number" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>" min="<?= $min ?>" max="<?= $max ?>" step="1">
                        <?php else: ?>
                            <input type="text" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endforeach; ?>
    </div>
</form>
