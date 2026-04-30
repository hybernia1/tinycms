<?php
if (!defined('BASE_DIR')) {
    exit;
}

$themes = is_array($themes ?? null) ? $themes : [];
$activeTheme = (string)($activeTheme ?? 'default');
$values = is_array($values ?? null) ? $values : [];
$fields = is_array($fields ?? null) ? $fields : [];
$section = (string)($section ?? 'overview');
$activeManifest = is_array($themes[$activeTheme] ?? null) ? $themes[$activeTheme] : [];
$sections = ['overview', 'settings'];

$fieldLabel = static function (string $key, array $field): string {
    $labelKey = trim((string)($field['label_key'] ?? ''));
    $fallback = trim((string)($field['label'] ?? $key));
    return $labelKey !== '' ? t($labelKey, $fallback) : $fallback;
};

$renderField = static function (string $fieldKey, array $field, string $fieldValue) use ($url, $imageUploadAccept, $imageUploadTypesLabel, $fieldLabel): void {
    $fieldType = (string)($field['type'] ?? 'text');
    $label = $fieldLabel($fieldKey, $field);
    ?>
    <div class="mb-3">
        <?php if ($fieldType !== 'checkbox'): ?>
            <label><?= esc_html($label) ?></label>
        <?php endif; ?>

        <?php if ($fieldType === 'textarea'): ?>
            <textarea name="theme[<?= esc_attr($fieldKey) ?>]" rows="4"><?= esc_html($fieldValue) ?></textarea>
        <?php elseif ($fieldType === 'select'): ?>
            <select name="theme[<?= esc_attr($fieldKey) ?>]">
                <?php foreach ((array)($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                    <?php $value = trim((string)$optionValue); ?>
                    <option value="<?= esc_attr($value) ?>"<?= $fieldValue === $value ? ' selected' : '' ?>>
                        <?= esc_html((string)$optionLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($fieldType === 'checkbox'): ?>
            <input type="hidden" name="theme[<?= esc_attr($fieldKey) ?>]" value="0">
            <label class="widget-builder-check">
                <input type="checkbox" name="theme[<?= esc_attr($fieldKey) ?>]" value="1"<?= $fieldValue === '1' ? ' checked' : '' ?>>
                <span><?= esc_html($label) ?></span>
            </label>
        <?php elseif ($fieldType === 'file'): ?>
            <?php $inputId = 'theme-media-' . preg_replace('/[^a-z0-9_-]/i', '-', $fieldKey); ?>
            <input id="<?= esc_attr($inputId) ?>" type="hidden" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
            <button
                class="content-thumbnail-trigger settings-media-trigger<?= $fieldValue === '' ? ' empty' : '' ?>"
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
                data-media-upload-types-label="<?= esc_attr((string)($imageUploadTypesLabel ?? '')) ?>"
                data-media-library-allow-delete="0"
                data-media-library-allow-rename="0"
            >
                <?php if ($fieldValue !== ''): ?>
                    <div class="settings-media-preview">
                        <img src="<?= esc_url($url($fieldValue)) ?>" alt="<?= esc_attr($label) ?>">
                    </div>
                <?php else: ?>
                    <span><?= esc_html(t('content.choose_image')) ?></span>
                <?php endif; ?>
            </button>
        <?php elseif ($fieldType === 'number'): ?>
            <input
                type="number"
                name="theme[<?= esc_attr($fieldKey) ?>]"
                value="<?= esc_attr($fieldValue) ?>"
                min="<?= esc_attr((string)($field['min'] ?? 0)) ?>"
                max="<?= esc_attr((string)($field['max'] ?? 1000)) ?>"
                step="1"
            >
        <?php else: ?>
            <input type="text" name="theme[<?= esc_attr($fieldKey) ?>]" value="<?= esc_attr($fieldValue) ?>">
        <?php endif; ?>
    </div>
    <?php
};
?>
<form
    id="themes-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/themes')) ?>"
    data-api-submit
>
    <?= $csrfField() ?>
    <input type="hidden" name="theme_section" value="<?= esc_attr($section) ?>">

    <nav class="filter-nav mb-3">
        <?php foreach ($sections as $sectionKey): ?>
            <a
                class="filter-link<?= $sectionKey === $section ? ' active' : '' ?>"
                href="<?= esc_url($url('admin/themes/' . $sectionKey)) ?>"
            >
                <?= esc_html(t('themes.sections.' . $sectionKey, ucfirst($sectionKey))) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($section === 'settings'): ?>
        <input type="hidden" name="theme[front_theme]" value="<?= esc_attr($activeTheme) ?>">
        <div class="card p-4">
            <?php if ($fields === []): ?>
                <p class="text-muted m-0"><?= esc_html(t('themes.no_settings')) ?></p>
            <?php else: ?>
                <?php foreach ($fields as $fieldKey => $field): ?>
                    <?php $renderField((string)$fieldKey, (array)$field, (string)($values[$fieldKey] ?? ($field['default'] ?? ''))); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card p-4">
            <div class="theme-overview">
                <div class="theme-overview-head">
                    <div class="theme-overview-title">
                        <div class="d-flex align-center gap-2">
                            <h2 class="m-0"><?= esc_html((string)($activeManifest['name'] ?? $activeTheme)) ?></h2>
                            <?php if (trim((string)($activeManifest['version'] ?? '')) !== ''): ?>
                                <span class="badge"><?= esc_html((string)$activeManifest['version']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (trim((string)($activeManifest['description'] ?? '')) !== ''): ?>
                            <p class="text-muted m-0"><?= esc_html((string)$activeManifest['description']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="theme-overview-picker">
                        <label><?= esc_html(t('themes.active_theme')) ?></label>
                        <select name="theme[front_theme]">
                            <?php foreach ($themes as $slug => $theme): ?>
                                <option value="<?= esc_attr((string)$slug) ?>"<?= (string)$slug === $activeTheme ? ' selected' : '' ?>>
                                    <?= esc_html((string)($theme['name'] ?? $slug)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <dl class="theme-overview-meta">
                    <div>
                        <dt><?= esc_html(t('common.author')) ?></dt>
                        <dd><?= esc_html((string)($activeManifest['author'] ?? '')) ?></dd>
                    </div>
                    <div>
                        <dt><?= esc_html(t('themes.slug')) ?></dt>
                        <dd><code><?= esc_html($activeTheme) ?></code></dd>
                    </div>
                    <div class="theme-overview-features">
                        <dt><?= esc_html(t('themes.features')) ?></dt>
                        <dd>
                        <?php foreach ((array)($activeManifest['features'] ?? []) as $feature): ?>
                            <?php $feature = (string)$feature; ?>
                            <span class="badge"><?= esc_html(t('themes.feature_labels.' . $feature, $feature)) ?></span>
                        <?php endforeach; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    <?php endif; ?>
</form>
