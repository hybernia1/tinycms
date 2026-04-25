<?php
if (!defined('BASE_DIR')) {
    exit;
}

$items = is_array($items ?? null) ? $items : [];
$icons = array_values(array_filter(array_map('strval', is_array($icons ?? null) ? $icons : [])));

$renderIconPicker = static function (string $selectedIcon, string $inputName = '', string $inputAttr = '') use ($icons): void {
    ?>
    <div class="menu-builder-icon-picker" data-menu-icon-picker>
        <input type="hidden"<?= $inputName !== '' ? ' name="' . esc_attr($inputName) . '"' : '' ?> value="<?= esc_attr($selectedIcon) ?>" data-menu-icon-value<?= $inputAttr !== '' ? ' ' . $inputAttr : '' ?>>
        <button
            class="btn btn-light btn-icon menu-builder-icon-trigger"
            type="button"
            data-menu-icon-trigger
            aria-label="<?= esc_attr(t('menu.item_icon')) ?>"
            aria-haspopup="true"
            aria-expanded="false"
        >
            <span class="menu-builder-icon-preview" data-menu-icon-preview aria-hidden="true">
                <?= $selectedIcon !== '' ? icon($selectedIcon) : '' ?>
            </span>
        </button>
        <div class="menu-builder-icon-options" data-menu-icon-options hidden>
            <button
                class="btn btn-light btn-icon menu-builder-icon-option<?= $selectedIcon === '' ? ' selected' : '' ?>"
                type="button"
                data-menu-icon-option
                data-icon=""
                aria-label="<?= esc_attr(t('menu.no_icon')) ?>"
            ></button>
            <?php foreach ($icons as $name): ?>
                <button
                    class="btn btn-light btn-icon menu-builder-icon-option<?= $selectedIcon === $name ? ' selected' : '' ?>"
                    type="button"
                    data-menu-icon-option
                    data-icon="<?= esc_attr($name) ?>"
                    aria-label="<?= esc_attr($name) ?>"
                >
                    <?= icon($name) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};

$renderItem = static function (array $item) use ($renderIconPicker): void {
    $target = (string)($item['link_target'] ?? '_self');
    ?>
    <div class="menu-builder-row" data-menu-item>
        <div class="menu-builder-fields">
            <div class="menu-builder-field form-floating-field menu-builder-field-label">
                <label><?= esc_html(t('menu.item_label')) ?></label>
                <div class="field-with-icon menu-builder-label-control">
                    <?php $renderIconPicker((string)($item['icon'] ?? ''), 'item_icon[]'); ?>
                    <input
                        class="menu-builder-label-input"
                        type="text"
                        name="item_label[]"
                        value="<?= esc_attr((string)($item['label'] ?? '')) ?>"
                        aria-label="<?= esc_attr(t('menu.item_label')) ?>"
                    >
                </div>
            </div>
            <div class="menu-builder-field form-floating-field menu-builder-field-url">
                <label><?= esc_html(t('menu.item_url')) ?></label>
                <div class="field-with-icon">
                    <span class="field-overlay field-overlay-start field-icon field-icon-soft" aria-hidden="true"><?= icon('w-link') ?></span>
                    <input
                        class="field-control-with-start-icon"
                        type="text"
                        name="item_url[]"
                        value="<?= esc_attr((string)($item['url'] ?? '')) ?>"
                        aria-label="<?= esc_attr(t('menu.item_url')) ?>"
                    >
                </div>
            </div>
            <div class="menu-builder-field form-floating-field menu-builder-field-target">
                <label><?= esc_html(t('menu.item_target')) ?></label>
                <select name="item_target[]" aria-label="<?= esc_attr(t('menu.item_target')) ?>">
                    <option value="_self"<?= $target === '_self' ? ' selected' : '' ?>><?= esc_html(t('menu.target_self')) ?></option>
                    <option value="_blank"<?= $target === '_blank' ? ' selected' : '' ?>><?= esc_html(t('menu.target_blank')) ?></option>
                </select>
            </div>
        </div>
        <div class="menu-builder-actions">
            <button class="btn btn-light btn-icon menu-builder-action" type="button" data-menu-item-up aria-label="<?= esc_attr(t('menu.move_up')) ?>" title="<?= esc_attr(t('menu.move_up')) ?>">
                <?= icon('next', 'icon menu-builder-icon-up') ?>
            </button>
            <button class="btn btn-light btn-icon menu-builder-action" type="button" data-menu-item-down aria-label="<?= esc_attr(t('menu.move_down')) ?>" title="<?= esc_attr(t('menu.move_down')) ?>">
                <?= icon('next', 'icon menu-builder-icon-down') ?>
            </button>
            <button class="btn btn-light btn-icon menu-builder-action menu-builder-action-danger" type="button" data-menu-item-remove aria-label="<?= esc_attr(t('menu.remove_item')) ?>" title="<?= esc_attr(t('menu.remove_item')) ?>">
                <?= icon('delete') ?>
            </button>
        </div>
    </div>
    <?php
};
?>
<form
    id="menu-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/menu')) ?>"
    data-api-submit
    data-stay-on-page
    data-menu-builder
>
    <?= $csrfField() ?>

    <div class="content-editor-layout">
        <div class="card p-4">
            <div class="d-flex justify-between align-center gap-2 mb-3 menu-builder-toolbar">
                <h2 class="m-0"><?= esc_html(t('menu.items')) ?></h2>
                <span class="badge menu-builder-count" data-menu-count>0</span>
            </div>
            <div class="menu-builder-items" data-menu-items>
                <?php foreach ($items as $item): ?>
                    <?php $renderItem((array)$item); ?>
                <?php endforeach; ?>
            </div>
            <p class="text-muted m-0 menu-builder-empty" data-menu-empty<?= $items === [] ? '' : ' hidden' ?>><?= esc_html(t('menu.empty')) ?></p>
        </div>

        <aside class="content-editor-sidebar">
            <div class="card" data-menu-draft>
                <div class="content-box-header"><?= esc_html(t('menu.add_item')) ?></div>
                <div class="p-3 menu-builder-add-fields">
                    <div class="menu-builder-field form-floating-field">
                        <label><?= esc_html(t('menu.item_label')) ?></label>
                        <div class="field-with-icon menu-builder-label-control">
                            <?php $renderIconPicker('', '', 'data-menu-draft-icon'); ?>
                            <input class="menu-builder-label-input" type="text" data-menu-draft-label>
                        </div>
                    </div>
                    <div class="menu-builder-field form-floating-field">
                        <label><?= esc_html(t('menu.item_url')) ?></label>
                        <div class="field-with-icon">
                            <span class="field-overlay field-overlay-start field-icon field-icon-soft" aria-hidden="true"><?= icon('w-link') ?></span>
                            <input class="field-control-with-start-icon" type="text" data-menu-draft-url>
                        </div>
                    </div>
                    <div class="menu-builder-field form-floating-field">
                        <label><?= esc_html(t('menu.item_target')) ?></label>
                        <select data-menu-draft-target>
                            <option value="_self"><?= esc_html(t('menu.target_self')) ?></option>
                            <option value="_blank"><?= esc_html(t('menu.target_blank')) ?></option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100 menu-builder-add-submit" type="button" data-menu-add-item>
                        <?= icon('add') ?>
                        <span><?= esc_html(t('menu.add_item')) ?></span>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <template data-menu-item-template>
        <?php $renderItem(['label' => '', 'url' => '', 'icon' => '', 'link_target' => '_self']); ?>
    </template>
</form>
