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
$activeSection = (string)($section ?? 'general');
if (!isset($sections[$activeSection])) {
    $activeSection = (string)(array_key_first($sections) ?? 'general');
}
$activeFields = (array)($sections[$activeSection] ?? []);
?>
<form
    id="settings-form"
    method="post"
    enctype="multipart/form-data"
    action="<?= esc_url($url('admin/api/v1/settings')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>
    <input type="hidden" name="settings_section" value="<?= esc_attr($activeSection) ?>">

    <nav class="filter-nav mb-3">
        <?php foreach ($sections as $sectionKey => $sectionFields): ?>
            <a
                class="filter-link<?= $sectionKey === $activeSection ? ' active' : '' ?>"
                href="<?= esc_url($url('admin/settings/' . (string)$sectionKey)) ?>"
            >
                <?= esc_html(t('settings.sections.' . $sectionKey, ucfirst((string)$sectionKey))) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="card p-4">
        <?php foreach ($activeFields as $fieldKey => $field):
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
                <?php elseif ($fieldType === 'content_picker'): ?>
                    <?php
                        $loopLabel = (string)($field['empty_label'] ?? t('settings.options.front_home_content.none'));
                        $selectedLabel = (string)($field['selected_label'] ?? '');
                    ?>
                    <div
                        class="tag-picker"
                        data-picker
                        data-picker-mode="single"
                        data-search-endpoint="<?= esc_attr($url('admin/api/v1/content')) ?>"
                        data-search-status="published"
                        data-empty-label="<?= esc_attr($loopLabel) ?>"
                        data-no-results-label="<?= esc_attr(t('common.no_results')) ?>"
                        data-search-placeholder="<?= esc_attr(t('settings.options.front_home_content.search_placeholder')) ?>"
                        data-selected-label="<?= esc_attr($selectedLabel) ?>"
                    >
                        <input type="hidden" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>" data-picker-value>
                        <div class="tag-picker-field">
                            <div class="tag-picker-chips" data-picker-chips></div>
                            <input
                                type="text"
                                class="tag-picker-input"
                                data-picker-input
                                autocomplete="off"
                                placeholder="<?= esc_attr(t('settings.options.front_home_content.search_placeholder')) ?>"
                            >
                        </div>
                        <div class="tag-picker-suggestions" data-picker-suggestions></div>
                    </div>
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
</form>
