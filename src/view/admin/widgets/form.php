<?php
if (!defined('BASE_DIR')) {
    exit;
}

$sidebars = is_array($sidebars ?? null) ? $sidebars : [];
$widgets = is_array($widgets ?? null) ? $widgets : [];
$widgetLayout = is_array($widgetLayout ?? null) ? $widgetLayout : [];

$settingValue = static function (array $settings, string $key, array $field): string {
    return (string)($settings[$key] ?? ($field['default'] ?? ''));
};

$renderItem = static function (string $sidebar, int $index, array $instance) use ($widgets, $settingValue): void {
    $type = (string)($instance['type'] ?? '');
    $definition = (array)($widgets[$type] ?? []);
    $fields = (array)($definition['fields'] ?? []);
    $settings = (array)($instance['settings'] ?? []);
    $id = (string)($instance['id'] ?? '');
    $enabled = (int)($instance['enabled'] ?? 1) === 1;
    $label = trim((string)($definition['label'] ?? $type));
    ?>
    <div class="widget-manager-item" data-widget-instance data-widget-type="<?= esc_attr($type) ?>">
        <input type="hidden" data-widget-input="id" name="widgets[<?= esc_attr($sidebar) ?>][<?= $index ?>][id]" value="<?= esc_attr($id) ?>">
        <input type="hidden" data-widget-input="type" name="widgets[<?= esc_attr($sidebar) ?>][<?= $index ?>][type]" value="<?= esc_attr($type) ?>">
        <input type="hidden" data-widget-input="enabled-hidden" name="widgets[<?= esc_attr($sidebar) ?>][<?= $index ?>][enabled]" value="0">

        <div class="widget-manager-item-header">
            <label class="widget-manager-toggle">
                <input
                    type="checkbox"
                    data-widget-input="enabled"
                    name="widgets[<?= esc_attr($sidebar) ?>][<?= $index ?>][enabled]"
                    value="1"
                    <?= $enabled ? 'checked' : '' ?>
                >
                <span><?= esc_html($label !== '' ? $label : $type) ?></span>
            </label>
            <div class="widget-manager-actions">
                <button class="btn btn-light btn-icon" type="button" data-widget-up aria-label="<?= esc_attr(t('widgets.move_up')) ?>" title="<?= esc_attr(t('widgets.move_up')) ?>">
                    <?= icon('next', 'icon menu-builder-icon-up') ?>
                </button>
                <button class="btn btn-light btn-icon" type="button" data-widget-down aria-label="<?= esc_attr(t('widgets.move_down')) ?>" title="<?= esc_attr(t('widgets.move_down')) ?>">
                    <?= icon('next', 'icon menu-builder-icon-down') ?>
                </button>
                <button class="btn btn-light btn-icon menu-builder-action-danger" type="button" data-widget-remove aria-label="<?= esc_attr(t('widgets.remove')) ?>" title="<?= esc_attr(t('widgets.remove')) ?>">
                    <?= icon('delete') ?>
                </button>
            </div>
        </div>

        <?php foreach ($fields as $fieldKey => $field): ?>
            <?php
                $key = (string)$fieldKey;
                $field = (array)$field;
                $fieldType = (string)($field['type'] ?? 'text');
                $fieldLabel = (string)($field['label'] ?? $key);
                $value = $settingValue($settings, $key, $field);
            ?>
            <div class="widget-manager-field">
                <label><?= esc_html($fieldLabel) ?></label>
                <?php if ($fieldType === 'textarea'): ?>
                    <textarea data-widget-setting="<?= esc_attr($key) ?>" name="widgets[<?= esc_attr($sidebar) ?>][<?= $index ?>][settings][<?= esc_attr($key) ?>]" rows="3"><?= esc_html($value) ?></textarea>
                <?php else: ?>
                    <input data-widget-setting="<?= esc_attr($key) ?>" type="text" name="widgets[<?= esc_attr($sidebar) ?>][<?= $index ?>][settings][<?= esc_attr($key) ?>]" value="<?= esc_attr($value) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
};
?>
<form
    id="widgets-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/widgets')) ?>"
    data-api-submit
    data-widget-manager
>
    <?= $csrfField() ?>

    <div class="widget-manager-grid">
        <?php foreach ($sidebars as $sidebarId => $sidebar): ?>
            <?php
                $sidebarKey = (string)$sidebarId;
                $instances = (array)($widgetLayout[$sidebarKey] ?? []);
                $sidebarLabel = trim((string)($sidebar['label'] ?? $sidebarKey));
            ?>
            <section class="card widget-manager-area" data-widget-area="<?= esc_attr($sidebarKey) ?>">
                <div class="content-box-header content-box-header-actions">
                    <span><?= esc_html($sidebarLabel !== '' ? $sidebarLabel : $sidebarKey) ?></span>
                    <span class="badge" data-widget-count><?= count($instances) ?></span>
                </div>
                <div class="widget-manager-list" data-widget-items>
                    <?php foreach ($instances as $index => $instance): ?>
                        <?php $renderItem($sidebarKey, (int)$index, (array)$instance); ?>
                    <?php endforeach; ?>
                </div>
                <div class="content-box-footer widget-manager-add">
                    <select data-widget-add-select aria-label="<?= esc_attr(t('widgets.add_widget')) ?>">
                        <?php foreach ($widgets as $widgetId => $widget): ?>
                            <option value="<?= esc_attr((string)$widgetId) ?>"><?= esc_html((string)($widget['label'] ?? $widgetId)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="button" data-widget-add<?= $widgets === [] ? ' disabled' : '' ?>>
                        <?= icon('add') ?>
                        <span><?= esc_html(t('widgets.add_widget')) ?></span>
                    </button>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <?php foreach ($widgets as $widgetId => $widget): ?>
        <template data-widget-template="<?= esc_attr((string)$widgetId) ?>">
            <?php $renderItem('__sidebar__', 0, [
                'id' => '',
                'type' => (string)$widgetId,
                'enabled' => 1,
                'settings' => [],
            ]); ?>
        </template>
    <?php endforeach; ?>
</form>
