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
                <?php elseif ($fieldType === 'file'): ?>
                    <?php $inputId = 'media-picker-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)$fieldKey); ?>
                    <input id="<?= esc_attr($inputId) ?>" type="hidden" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
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
                        data-media-upload-accept="<?= esc_attr((string)($imageUploadAccept ?? '')) ?>"
                        data-media-library-allow-delete="0"
                        data-media-library-allow-rename="0"
                    >
                        <?php if ($fieldValue !== ''): ?>
                            <div class="media-picker-preview-compact">
                                <img src="<?= esc_url($url($fieldValue)) ?>" alt="<?= esc_attr((string)$fieldKey) ?> preview">
                            </div>
                        <?php else: ?>
                            <span><?= esc_html(t('content.choose_image')) ?></span>
                        <?php endif; ?>
                    </button>
                <?php elseif ($fieldType === 'number'): ?>
                    <?php $min = (int)($field['min'] ?? 1); ?>
                    <?php $max = (int)($field['max'] ?? 100); ?>
                    <input type="number" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>" min="<?= $min ?>" max="<?= $max ?>" step="1">
                <?php elseif ($fieldType === 'password'): ?>
                    <input type="password" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="" autocomplete="new-password">
                <?php else: ?>
                    <input type="text" name="settings[<?= esc_attr((string)$fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</form>
