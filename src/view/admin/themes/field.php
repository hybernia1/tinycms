<?php
if (!defined('BASE_DIR')) {
    exit;
}

$fieldKey = (string)$fieldKey;
$field = is_array($field ?? null) ? $field : [];
$fieldValue = (string)($fieldValue ?? '');
$fieldType = (string)($field['type'] ?? 'text');
$imageUploadAccept = (string)($imageUploadAccept ?? '');
$imageUploadTypesLabel = (string)($imageUploadTypesLabel ?? '');
$label = trim((string)($field['label'] ?? ''));
$label = $label !== '' ? $label : $fieldKey;
$fieldClasses = ['customizer-field', 'customizer-field-' . $fieldType];
if ($fieldType !== 'checkbox') {
    $fieldClasses[] = 'form-floating-field';
}
$colorIsTransparent = strtolower(trim($fieldValue)) === 'transparent';
$colorDefaultValue = strtolower(trim((string)($field['default'] ?? '')));
$colorPickerValue = preg_match('/^#[0-9a-f]{6}$/i', $fieldValue) === 1 ? strtolower($fieldValue) : '#000000';
if ($colorIsTransparent && preg_match('/^#[0-9a-f]{6}$/i', $colorDefaultValue) === 1) {
    $colorPickerValue = $colorDefaultValue;
}
$colorFieldValue = $colorIsTransparent ? 'transparent' : $colorPickerValue;
?>
<div class="<?= esc_attr(implode(' ', $fieldClasses)) ?>">
    <?php if ($fieldType === 'checkbox'): ?>
        <input type="hidden" name="theme[<?= esc_attr($fieldKey) ?>]" value="0">
        <label class="customizer-switch">
            <input type="checkbox" name="theme[<?= esc_attr($fieldKey) ?>]" value="1"<?= $fieldValue === '1' ? ' checked' : '' ?>>
            <span><?= esc_html($label) ?></span>
        </label>
    <?php else: ?>
        <label><?= esc_html($label) ?></label>
        <?php if ($fieldType === 'textarea'): ?>
            <textarea name="theme[<?= esc_attr($fieldKey) ?>]" rows="<?= $fieldKey === 'custom_css' ? 10 : 4 ?>"><?= esc_html($fieldValue) ?></textarea>
        <?php elseif ($fieldType === 'select'): ?>
            <select name="theme[<?= esc_attr($fieldKey) ?>]">
                <?php foreach ((array)($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                    <?php $value = trim((string)$optionValue); ?>
                    <option value="<?= esc_attr($value) ?>"<?= $fieldValue === $value ? ' selected' : '' ?>>
                        <?= esc_html((string)$optionLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($fieldType === 'file'): ?>
            <?php $inputId = 'customizer-media-' . preg_replace('/[^a-z0-9_-]/i', '-', $fieldKey); ?>
            <input id="<?= esc_attr($inputId) ?>" type="hidden" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
            <button
                class="media-picker-trigger media-picker-trigger-compact<?= $fieldValue === '' ? ' empty' : '' ?>"
                type="button"
                data-media-library-open
                data-media-library-mode="settings"
                data-media-library-endpoint="<?= esc_attr($url('admin/api/v1/media')) ?>"
                data-media-base-url="<?= esc_attr($url('')) ?>"
                data-media-target-input="#<?= esc_attr($inputId) ?>"
                data-current-media-path="<?= esc_attr($fieldValue) ?>"
                data-media-library-per-page="<?= defined('APP_POSTS_PER_PAGE') ? (int)APP_POSTS_PER_PAGE : 10 ?>"
                data-media-upload-endpoint="<?= esc_attr($url('admin/api/v1/media/add')) ?>"
                data-media-upload-name="file"
                data-media-upload-accept="<?= esc_attr((string)$imageUploadAccept) ?>"
                data-media-library-allow-delete="0"
                data-media-library-allow-rename="0"
            >
                <?php if ($fieldValue !== ''): ?>
                    <div class="media-picker-preview-compact">
                        <img src="<?= esc_url($url($fieldValue)) ?>" alt="<?= esc_attr($label) ?>">
                    </div>
                <?php else: ?>
                    <span><?= esc_html(t('content.choose_image')) ?></span>
                <?php endif; ?>
            </button>
        <?php elseif ($fieldType === 'content_picker'): ?>
            <?php
                $loopLabel = (string)($field['empty_label'] ?? '');
                $placeholder = (string)($field['placeholder'] ?? '');
                $selectedLabel = (string)($field['selected_label'] ?? '');
            ?>
            <div
                class="tag-picker"
                data-picker
                data-picker-mode="single"
                data-search-endpoint="<?= esc_attr($url('admin/api/v1/content')) ?>"
                data-search-public="1"
                data-empty-label="<?= esc_attr($loopLabel) ?>"
                data-no-results-label="<?= esc_attr(t('common.no_results')) ?>"
                data-search-placeholder="<?= esc_attr($placeholder) ?>"
                data-selected-label="<?= esc_attr($selectedLabel) ?>"
            >
                <input type="hidden" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>" data-picker-value>
                <div class="tag-picker-field">
                    <div class="tag-picker-chips" data-picker-chips></div>
                    <input
                        type="text"
                        class="tag-picker-input"
                        data-picker-input
                        autocomplete="off"
                        placeholder="<?= esc_attr($placeholder) ?>"
                    >
                </div>
                <div class="tag-picker-suggestions" data-picker-suggestions></div>
            </div>
        <?php elseif ($fieldType === 'number'): ?>
            <input
                type="number"
                name="theme[<?= esc_attr($fieldKey) ?>]"
                value="<?= esc_attr($fieldValue) ?>"
                min="<?= esc_attr((string)($field['min'] ?? 0)) ?>"
                max="<?= esc_attr((string)($field['max'] ?? 1000)) ?>"
                step="1"
            >
        <?php elseif ($fieldType === 'color'): ?>
            <div class="customizer-color-control" data-color-field>
                <input type="hidden" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($colorFieldValue) ?>" data-color-value>
                <input type="color" value="<?= esc_attr($colorPickerValue) ?>" data-color-picker<?= $colorIsTransparent ? ' disabled' : '' ?>>
                <label class="customizer-color-transparent">
                    <input type="checkbox" value="1" data-color-transparent<?= $colorIsTransparent ? ' checked' : '' ?>>
                    <span><?= esc_html(t('themes.color_transparent')) ?></span>
                </label>
            </div>
        <?php else: ?>
            <input type="text" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
        <?php endif; ?>
    <?php endif; ?>
</div>
