<?php
if (!defined('BASE_DIR')) {
    exit;
}

$items = is_array($items ?? null) ? $items : [];
$icons = array_values(array_filter(array_map('strval', is_array($icons ?? null) ? $icons : [])));

$renderItem = static function (array $item) use ($e, $t, $icon, $icons): void {
    $target = (string)($item['link_target'] ?? '_self');
    $selectedIcon = (string)($item['icon'] ?? '');
    ?>
    <div class="menu-builder-row" data-menu-item>
        <div class="menu-builder-position" data-menu-item-index>1</div>
        <div class="menu-builder-fields">
            <div class="menu-builder-field menu-builder-field-icon">
                <div class="menu-builder-icon-picker" data-menu-icon-picker>
                    <input type="hidden" name="item_icon[]" value="<?= $e($selectedIcon) ?>" data-menu-icon-value>
                    <button
                        class="menu-builder-icon-trigger"
                        type="button"
                        data-menu-icon-trigger
                        aria-label="<?= $e($t('menu.item_icon')) ?>"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <span class="menu-builder-icon-preview" data-menu-icon-preview aria-hidden="true">
                            <?= $selectedIcon !== '' ? $icon($selectedIcon) : $icon('cancel') ?>
                        </span>
                    </button>
                    <div class="menu-builder-icon-options" data-menu-icon-options hidden>
                        <button
                            class="menu-builder-icon-option<?= $selectedIcon === '' ? ' selected' : '' ?>"
                            type="button"
                            data-menu-icon-option
                            data-icon=""
                            aria-label="<?= $e($t('menu.no_icon')) ?>"
                        >
                            <?= $icon('cancel') ?>
                        </button>
                        <?php foreach ($icons as $name): ?>
                            <button
                                class="menu-builder-icon-option<?= $selectedIcon === $name ? ' selected' : '' ?>"
                                type="button"
                                data-menu-icon-option
                                data-icon="<?= $e($name) ?>"
                                aria-label="<?= $e($name) ?>"
                            >
                                <?= $icon($name) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="menu-builder-field menu-builder-field-label">
                <div class="field-with-icon">
                    <span class="field-overlay field-overlay-start field-icon field-icon-soft" aria-hidden="true"><?= $icon('menu') ?></span>
                    <input
                        class="field-control-with-start-icon"
                        type="text"
                        name="item_label[]"
                        value="<?= $e((string)($item['label'] ?? '')) ?>"
                        aria-label="<?= $e($t('menu.item_label')) ?>"
                    >
                </div>
            </div>
            <div class="menu-builder-field menu-builder-field-url">
                <div class="field-with-icon">
                    <span class="field-overlay field-overlay-start field-icon field-icon-soft" aria-hidden="true"><?= $icon('w-link') ?></span>
                    <input
                        class="field-control-with-start-icon"
                        type="text"
                        name="item_url[]"
                        value="<?= $e((string)($item['url'] ?? '')) ?>"
                        aria-label="<?= $e($t('menu.item_url')) ?>"
                    >
                </div>
            </div>
            <div class="menu-builder-field menu-builder-field-target">
                <select name="item_target[]" aria-label="<?= $e($t('menu.item_target')) ?>">
                    <option value="_self"<?= $target === '_self' ? ' selected' : '' ?>><?= $e($t('menu.target_self')) ?></option>
                    <option value="_blank"<?= $target === '_blank' ? ' selected' : '' ?>><?= $e($t('menu.target_blank')) ?></option>
                </select>
            </div>
        </div>
        <div class="menu-builder-actions">
            <button class="btn btn-light btn-icon menu-builder-action" type="button" data-menu-item-up aria-label="<?= $e($t('menu.move_up')) ?>" title="<?= $e($t('menu.move_up')) ?>">
                <?= $icon('next', 'icon menu-builder-icon-up') ?>
            </button>
            <button class="btn btn-light btn-icon menu-builder-action" type="button" data-menu-item-down aria-label="<?= $e($t('menu.move_down')) ?>" title="<?= $e($t('menu.move_down')) ?>">
                <?= $icon('next', 'icon menu-builder-icon-down') ?>
            </button>
            <button class="btn btn-light btn-icon menu-builder-action menu-builder-action-danger" type="button" data-menu-item-remove aria-label="<?= $e($t('menu.remove_item')) ?>" title="<?= $e($t('menu.remove_item')) ?>">
                <?= $icon('delete') ?>
            </button>
        </div>
    </div>
    <?php
};
?>
<form
    id="menu-form"
    method="post"
    action="<?= $e($url('admin/api/v1/menu')) ?>"
    data-api-submit
    data-stay-on-page
    data-menu-builder
>
    <?= $csrfField() ?>

    <div class="card menu-builder-card">
        <div class="d-flex justify-between align-center gap-2 mb-3 menu-builder-toolbar">
            <h2 class="m-0"><?= $e($t('menu.items')) ?></h2>
            <button class="btn btn-light" type="button" data-menu-add-item>
                <?= $icon('add') ?>
                <span><?= $e($t('menu.add_item')) ?></span>
            </button>
        </div>
        <div class="menu-builder-items" data-menu-items>
            <?php foreach ($items as $item): ?>
                <?php $renderItem((array)$item); ?>
            <?php endforeach; ?>
        </div>
        <p class="text-muted m-0 menu-builder-empty" data-menu-empty<?= $items === [] ? '' : ' hidden' ?>><?= $e($t('menu.empty')) ?></p>
    </div>

    <template data-menu-item-template>
        <?php $renderItem(['label' => '', 'url' => '', 'icon' => '', 'link_target' => '_self']); ?>
    </template>
</form>
