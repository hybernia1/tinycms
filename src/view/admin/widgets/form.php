<?php
if (!defined('BASE_DIR')) {
    exit;
}

$items = is_array($items ?? null) ? $items : [];
$widgets = is_array($widgets ?? null) ? $widgets : [];
$areas = array_values(array_filter(array_map('strval', is_array($areas ?? null) ? $areas : [])));
$areaLabels = is_array($areaLabels ?? null) ? $areaLabels : [];
$defaultArea = (string)($areas[0] ?? '');
$itemsByArea = array_fill_keys($areas, []);
foreach ($items as $item) {
    $area = (string)($item['area'] ?? '');
    if (array_key_exists($area, $itemsByArea)) {
        $itemsByArea[$area][] = (array)$item;
    }
}

$renderFields = static function (array $definition, array $data, string $index): void {
    foreach ((array)($definition['fields'] ?? []) as $field):
        if (!is_array($field)) {
            continue;
        }

        $name = trim((string)($field['name'] ?? ''));
        if ($name === '' || preg_match('/^[a-z0-9_-]+$/i', $name) !== 1) {
            continue;
        }

        $type = (string)($field['type'] ?? 'text');
        $label = trim((string)($field['label'] ?? $name));
        $value = (string)($data[$name] ?? '');
        $inputName = 'item_data[' . $index . '][' . $name . ']';
        $fieldAttr = $name === 'title' ? ' data-widget-title-input' : '';
        ?>
        <div class="widget-builder-field form-floating-field">
            <label><?= esc_html($label !== '' ? $label : $name) ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea name="<?= esc_attr($inputName) ?>" rows="4"<?= $fieldAttr ?>><?= esc_html($value) ?></textarea>
            <?php elseif ($type === 'select'): ?>
                <select name="<?= esc_attr($inputName) ?>">
                    <?php foreach ((array)($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                        <?php $optionValue = (string)$optionValue; ?>
                        <option value="<?= esc_attr($optionValue) ?>"<?= $value === $optionValue ? ' selected' : '' ?>><?= esc_html((string)$optionLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'checkbox'): ?>
                <input type="hidden" name="<?= esc_attr($inputName) ?>" value="0">
                <label class="widget-builder-check">
                    <input type="checkbox" name="<?= esc_attr($inputName) ?>" value="1"<?= $value === '1' ? ' checked' : '' ?>>
                    <span><?= esc_html($label !== '' ? $label : $name) ?></span>
                </label>
            <?php elseif ($type === 'number'): ?>
                <input
                    type="number"
                    name="<?= esc_attr($inputName) ?>"
                    value="<?= esc_attr($value !== '' ? $value : (string)($field['default'] ?? '')) ?>"
                    min="<?= esc_attr((string)($field['min'] ?? 0)) ?>"
                    max="<?= esc_attr((string)($field['max'] ?? 1000)) ?>"
                    <?= $fieldAttr ?>
                >
            <?php else: ?>
                <input type="text" name="<?= esc_attr($inputName) ?>" value="<?= esc_attr($value) ?>"<?= $fieldAttr ?>>
            <?php endif; ?>
        </div>
        <?php
    endforeach;
};

$renderRow = static function (array $item, string $index) use ($widgets, $renderFields): void {
    $widget = (string)($item['widget'] ?? '');
    $definition = is_array($widgets[$widget] ?? null) ? $widgets[$widget] : [];
    $area = (string)($item['area'] ?? '');
    $data = is_array($item['data'] ?? null) ? $item['data'] : [];
    $label = (string)($definition['name'] ?? $widget);
    $title = trim((string)($data['title'] ?? ''));
    ?>
    <div class="widget-builder-row" data-widget-item data-widget-label="<?= esc_attr($label) ?>">
        <input type="hidden" name="item_widget[<?= esc_attr($index) ?>]" value="<?= esc_attr($widget) ?>">
        <input type="hidden" name="item_area[<?= esc_attr($index) ?>]" value="<?= esc_attr($area) ?>" data-widget-item-area>
        <input type="hidden" name="item_active[<?= esc_attr($index) ?>]" value="1">
        <div class="widget-builder-summary">
            <button class="btn btn-light btn-icon builder-drag-handle" type="button" draggable="true" data-builder-drag-handle aria-label="<?= esc_attr(t('widgets.move_item')) ?>" title="<?= esc_attr(t('widgets.move_item')) ?>">
                <?= icon('menu') ?>
            </button>
            <button class="btn btn-light btn-icon widget-builder-toggle" type="button" data-widget-item-toggle aria-expanded="false" aria-label="<?= esc_attr(t('widgets.configure')) ?>" title="<?= esc_attr(t('widgets.configure')) ?>">
                <?= icon('next', 'icon widget-builder-toggle-icon') ?>
            </button>
            <div class="widget-builder-summary-text">
                <strong data-widget-item-label><?= esc_html($label) ?></strong>
                <span class="text-muted small" data-widget-item-title<?= $title === '' ? ' hidden' : '' ?>><?= esc_html($title) ?></span>
            </div>
            <div class="table-col-actions widget-builder-actions">
                <button class="btn btn-light btn-icon" type="button" data-widget-item-up aria-label="<?= esc_attr(t('widgets.move_up')) ?>" title="<?= esc_attr(t('widgets.move_up')) ?>">
                    <?= icon('next', 'icon builder-icon-up') ?>
                </button>
                <button class="btn btn-light btn-icon" type="button" data-widget-item-down aria-label="<?= esc_attr(t('widgets.move_down')) ?>" title="<?= esc_attr(t('widgets.move_down')) ?>">
                    <?= icon('next', 'icon builder-icon-down') ?>
                </button>
                <button class="btn btn-light btn-icon widget-builder-action-danger" type="button" data-widget-item-remove aria-label="<?= esc_attr(t('widgets.remove_item')) ?>" title="<?= esc_attr(t('widgets.remove_item')) ?>">
                    <?= icon('delete') ?>
                </button>
            </div>
        </div>
        <div class="widget-builder-details" data-widget-item-details hidden>
            <div class="widget-builder-form">
                <div class="widget-builder-field form-floating-field">
                    <label><?= esc_html(t('widgets.widget')) ?></label>
                    <input type="text" value="<?= esc_attr($label) ?>" readonly>
                </div>
            <?php $renderFields($definition, $data, $index); ?>
            </div>
        </div>
    </div>
    <?php
};
?>
<form
    id="widgets-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/widgets')) ?>"
    data-api-submit
    data-stay-on-page
    data-widget-builder
>
    <?= $csrfField() ?>

    <div class="content-editor-layout">
        <div class="widget-builder-areas">
            <?php foreach ($areas as $area): ?>
                <?php $areaItems = $itemsByArea[$area] ?? []; ?>
                <div class="card p-4 widget-builder-area" data-widget-area="<?= esc_attr($area) ?>">
                    <div class="d-flex justify-between align-center gap-2 mb-3 builder-toolbar widget-builder-area-header">
                        <h2 class="m-0"><?= esc_html((string)($areaLabels[$area] ?? $area)) ?></h2>
                        <span class="badge builder-count" data-widget-area-count>0</span>
                    </div>
                    <div class="builder-items" data-widget-items>
                        <?php foreach ($areaItems as $item): ?>
                            <?php $renderRow($item, '__INDEX__'); ?>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted m-0 builder-empty" data-widget-area-empty<?= $areaItems === [] ? '' : ' hidden' ?>><?= esc_html(t('widgets.empty')) ?></p>
                    <?php if ($widgets !== []): ?>
                        <div class="widget-builder-add" data-widget-add-area="<?= esc_attr($area) ?>">
                            <button class="btn btn-light btn-icon widget-builder-add-toggle" type="button" data-widget-add-toggle aria-expanded="false" aria-label="<?= esc_attr(t('widgets.add_item')) ?>" title="<?= esc_attr(t('widgets.add_item')) ?>">
                                <?= icon('add') ?>
                            </button>
                            <div class="widget-builder-add-form" data-widget-add-form hidden>
                                <div class="widget-builder-field form-floating-field">
                                    <label><?= esc_html(t('widgets.widget')) ?></label>
                                    <select data-widget-add-select>
                                        <?php foreach ($widgets as $name => $definition): ?>
                                            <option value="<?= esc_attr((string)$name) ?>"><?= esc_html((string)($definition['name'] ?? $name)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="btn btn-primary btn-icon" type="button" data-widget-add-item aria-label="<?= esc_attr(t('widgets.add_item')) ?>" title="<?= esc_attr(t('widgets.add_item')) ?>">
                                    <?= icon('add') ?>
                                </button>
                                <button class="btn btn-light btn-icon" type="button" data-widget-add-cancel aria-label="<?= esc_attr(t('common.cancel')) ?>" title="<?= esc_attr(t('common.cancel')) ?>">
                                    <?= icon('cancel') ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($areas === []): ?>
                <div class="card p-4">
                    <p class="text-muted m-0"><?= esc_html(t('widgets.no_areas')) ?></p>
                </div>
            <?php elseif ($widgets === []): ?>
                <div class="card p-4">
                    <p class="text-muted m-0"><?= esc_html(t('widgets.no_widgets')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($widgets as $name => $definition): ?>
        <template data-widget-template="<?= esc_attr((string)$name) ?>">
            <?php $renderRow(['area' => $defaultArea, 'widget' => (string)$name, 'data' => [], 'active' => 1], '__INDEX__'); ?>
        </template>
    <?php endforeach; ?>
</form>
