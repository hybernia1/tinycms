<?php
if (!defined('BASE_DIR')) {
    exit;
}

$activeTheme = (string)$activeTheme;
$activeThemeName = (string)$activeThemeName;
$previewUrl = trim((string)$previewUrl);
$previewBase = $previewUrl !== '' ? $previewUrl : $absoluteUrl('');

$fieldLabel = static function (string $key, array $field): string {
    $label = trim((string)$field['label']);
    return $label !== '' ? $label : $key;
};

$sectionLabel = static function (string $key, array $section): string {
    $label = trim((string)$section['label']);
    return $label !== '' ? $label : $key;
};

$renderField = static function (string $fieldKey, array $field, string $fieldValue) use ($url, $imageUploadAccept, $imageUploadTypesLabel, $fieldLabel): void {
    $fieldType = (string)$field['type'];
    $label = $fieldLabel($fieldKey, $field);
    ?>
    <div class="customizer-field customizer-field-<?= esc_attr($fieldType) ?>">
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
                    <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
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
                    data-media-upload-types-label="<?= esc_attr((string)$imageUploadTypesLabel) ?>"
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
                    $loopLabel = (string)$field['empty_label'];
                    $placeholder = (string)$field['placeholder'];
                    $selectedLabel = (string)$field['selected_label'];
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
                    min="<?= esc_attr((string)$field['min']) ?>"
                    max="<?= esc_attr((string)$field['max']) ?>"
                    step="1"
                >
            <?php elseif ($fieldType === 'color'): ?>
                <input type="color" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
            <?php else: ?>
                <input type="text" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
};
?>
<div class="theme-customizer" data-customizer-root>
    <aside class="customizer-panel">
        <div class="customizer-panel-head">
            <div>
                <a class="customizer-back" href="<?= esc_url($url('admin/themes')) ?>"><?= icon('prev') ?><span><?= esc_html(t('themes.back_to_themes')) ?></span></a>
                <h1><?= esc_html(t('themes.customizer')) ?></h1>
                <p><?= esc_html($activeThemeName) ?></p>
            </div>
            <button class="btn btn-primary btn-icon customizer-save" type="button" data-customizer-save aria-label="<?= esc_attr(t('common.save')) ?>" title="<?= esc_attr(t('common.save')) ?>">
                <?= icon('save') ?>
            </button>
        </div>

        <div class="customizer-controls">
            <form
                id="theme-customizer-form"
                method="post"
                action="<?= esc_url($url('admin/api/v1/themes')) ?>"
                data-api-submit
                data-stay-on-page
                data-theme-customizer
                data-preview-frame="#theme-customizer-preview"
                data-preview-base="<?= esc_attr($previewBase) ?>"
                data-customizer-url="<?= esc_attr($url('customizer')) ?>"
            >
                <?= $csrfField() ?>
                <input type="hidden" name="theme[front_theme]" value="<?= esc_attr($activeTheme) ?>">

                <section class="customizer-screen is-active" data-customizer-screen="main">
                    <div class="customizer-nav-list">
                        <?php foreach ($customizerSections as $sectionKey => $customizerSection): ?>
                            <button class="customizer-nav-item" type="button" data-customizer-open="<?= esc_attr('theme-' . $sectionKey) ?>">
                                <span><?= esc_html($sectionLabel((string)$sectionKey, $customizerSection)) ?></span>
                                <?= icon('next') ?>
                            </button>
                        <?php endforeach; ?>
                        <button class="customizer-nav-item" type="button" data-customizer-open="menu">
                            <span><?= esc_html(t('admin.menu.menu')) ?></span>
                            <?= icon('next') ?>
                        </button>
                        <button class="customizer-nav-item" type="button" data-customizer-open="widgets">
                            <span><?= esc_html(t('admin.menu.widgets')) ?></span>
                            <?= icon('next') ?>
                        </button>
                    </div>
                </section>

                <?php foreach ($customizerSections as $sectionKey => $customizerSection): ?>
                    <section class="customizer-screen" data-customizer-screen="<?= esc_attr('theme-' . $sectionKey) ?>">
                        <div class="customizer-subhead">
                            <button class="customizer-subhead-back" type="button" data-customizer-back="main" aria-label="<?= esc_attr(t('common.back')) ?>" title="<?= esc_attr(t('common.back')) ?>"><?= icon('prev') ?></button>
                            <h2><?= esc_html($sectionLabel((string)$sectionKey, $customizerSection)) ?></h2>
                        </div>
                        <div class="customizer-section-fields">
                            <?php foreach ($customizerSection['fields'] as $fieldKey): ?>
                                <?php $field = $fields[$fieldKey]; ?>
                                <?php $renderField((string)$fieldKey, $field, (string)($values[$fieldKey] ?? $field['default'])); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </form>

            <section class="customizer-screen" data-customizer-screen="menu">
                <div class="customizer-subhead">
                    <button class="customizer-subhead-back" type="button" data-customizer-back="main" aria-label="<?= esc_attr(t('common.back')) ?>" title="<?= esc_attr(t('common.back')) ?>"><?= icon('prev') ?></button>
                    <h2><?= esc_html(t('admin.menu.menu')) ?></h2>
                </div>
                <div class="customizer-menu-fields">
                    <?php
                        $items = $menuItems;
                        $icons = $menuIcons;
                        $formId = 'customizer-menu-form';
                        $formAttrs = 'data-preview-refresh-on-success';
                        $layoutClass = 'customizer-menu-layout';
                        require BASE_DIR . '/' . VIEW_DIR . 'admin/menu/form.php';
                    ?>
                </div>
            </section>

            <form
                id="customizer-widgets-form"
                method="post"
                action="<?= esc_url($url('admin/api/v1/widgets')) ?>"
                data-api-submit
                data-stay-on-page
                data-widget-builder
                data-customizer-widgets
                data-preview-refresh-on-success
            >
                <?= $csrfField() ?>
                <section class="customizer-screen" data-customizer-screen="widgets" data-customizer-widget-section>
                    <div class="customizer-subhead">
                        <button class="customizer-subhead-back" type="button" data-customizer-back="main" aria-label="<?= esc_attr(t('common.back')) ?>" title="<?= esc_attr(t('common.back')) ?>"><?= icon('prev') ?></button>
                        <h2><?= esc_html(t('admin.menu.widgets')) ?></h2>
                    </div>
                    <?php if ($widgetAreas !== [] && $widgets !== []): ?>
                    <div class="customizer-nav-list">
                        <?php foreach ($widgetAreas as $area): ?>
                            <?php $area = (string)$area; ?>
                            <button class="customizer-nav-item" type="button" data-customizer-open="<?= esc_attr('widget-area-' . $area) ?>">
                                <span><?= esc_html((string)($widgetAreaLabels[$area] ?? $area)) ?></span>
                                <?= icon('next') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($widgets === []): ?>
                    <p class="text-muted m-0"><?= esc_html(t('widgets.no_widgets')) ?></p>
                    <?php else: ?>
                    <p class="text-muted m-0"><?= esc_html(t('widgets.no_areas')) ?></p>
                    <?php endif; ?>
                </section>

                <div class="customizer-widget-fields">
                        <?php
                            $items = $widgetItems;
                            $areas = $widgetAreas;
                            $areaLabels = $widgetAreaLabels;
                            $builderAreaScreensPrefix = 'widget-area-';
                            require BASE_DIR . '/' . VIEW_DIR . 'admin/widgets/builder.php';
                        ?>
                </div>
            </form>
        </div>
    </aside>

    <section class="customizer-preview">
        <div class="customizer-preview-bar">
            <span><?= esc_html(t('themes.live_preview')) ?></span>
            <a href="<?= esc_url($previewBase) ?>" target="_blank" rel="noopener noreferrer" data-preview-open><?= icon('show') ?><span><?= esc_html(t('themes.open_site')) ?></span></a>
        </div>
        <iframe
            id="theme-customizer-preview"
            title="<?= esc_attr(t('themes.live_preview')) ?>"
            sandbox="allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox"
        ></iframe>
    </section>
</div>
