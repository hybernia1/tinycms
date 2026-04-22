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
    action="<?= $escUrl($url('admin/api/v1/settings')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>

    <nav class="filter-nav settings-tabs mb-3" data-settings-tabs>
        <?php foreach ($sections as $sectionKey => $sectionFields): ?>
            <button
                class="filter-link<?= $sectionKey === array_key_first($sections) ? ' active' : '' ?>"
                type="button"
                data-settings-tab="<?= $escHtml((string)$sectionKey) ?>"
            >
                <?= $escHtml($t('settings.sections.' . $sectionKey, ucfirst((string)$sectionKey))) ?>
            </button>
        <?php endforeach; ?>
    </nav>

    <div class="card p-4">
        <?php foreach ($sections as $sectionKey => $sectionFields): ?>
            <div data-settings-tab-panel="<?= $escHtml((string)$sectionKey) ?>" <?= $sectionKey === array_key_first($sections) ? '' : 'hidden' ?>>
                <?php foreach ($sectionFields as $fieldKey => $field):
                    $fieldType = (string)($field['type'] ?? 'text');
                    $fieldValue = (string)($values[$fieldKey] ?? '');
                    $labelKey = (string)($field['label_key'] ?? ('settings.fields.' . $fieldKey));
                ?>
                    <div class="mb-3">
                        <label><?= $escHtml($t($labelKey, (string)$fieldKey)) ?></label>
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea name="settings[<?= $escHtml((string)$fieldKey) ?>]" rows="4"><?= $escHtml($fieldValue) ?></textarea>
                        <?php elseif ($fieldType === 'select'): ?>
                            <?php $options = (array)($field['options'] ?? []); ?>
                            <select name="settings[<?= $escHtml((string)$fieldKey) ?>]">
                                <?php foreach ($options as $optionValue => $optionLabel): ?>
                                    <?php $value = trim((string)$optionValue); ?>
                                    <option value="<?= $escHtml($value) ?>" <?= $fieldValue === $value ? 'selected' : '' ?>>
                                        <?= $escHtml((string)$optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($fieldType === 'file'): ?>
                            <?php $inputName = $fieldKey === 'logo' ? 'logo_file' : 'favicon_file'; ?>
                            <?php $fileInputId = 'settings-file-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)$fieldKey); ?>
                            <div class="custom-upload-field">
                                <label class="btn btn-light custom-upload-button" for="<?= $escHtml($fileInputId) ?>">
                                    <?= $icon('upload') ?>
                                    <span class="custom-upload-label" data-custom-upload-label data-default-label="<?= $escHtml($t('common.upload_add_files')) ?>"><?= $escHtml($t('common.upload_add_files')) ?></span>
                                </label>
                                <input id="<?= $escHtml($fileInputId) ?>" type="file" name="<?= $escHtml($inputName) ?>" accept="<?= $escHtml((string)($siteImageUploadAccept ?? '')) ?>">
                            </div>
                            <small class="text-muted d-block mt-2"><?= $escHtml(sprintf($t('common.allowed_upload_types'), (string)($siteImageUploadTypesLabel ?? ''))) ?></small>
                            <?php if ($fieldValue !== ''): ?>
                                <div class="settings-file-preview">
                                    <div class="text-muted"><?= $escHtml($fieldValue) ?></div>
                                    <img src="<?= $escUrl($url($fieldValue)) ?>" alt="<?= $escHtml((string)$fieldKey) ?> preview">
                                </div>
                            <?php endif; ?>
                        <?php elseif ($fieldType === 'number'): ?>
                            <?php $min = (int)($field['min'] ?? 1); ?>
                            <?php $max = (int)($field['max'] ?? 100); ?>
                            <input type="number" name="settings[<?= $escHtml((string)$fieldKey) ?>]" value="<?= $escHtml($fieldValue) ?>" min="<?= $min ?>" max="<?= $max ?>" step="1">
                        <?php else: ?>
                            <input type="text" name="settings[<?= $escHtml((string)$fieldKey) ?>]" value="<?= $escHtml($fieldValue) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endforeach; ?>
    </div>
</form>
