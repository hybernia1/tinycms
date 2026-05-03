<?php
if (!defined('BASE_DIR')) {
    exit;
}

$activeTheme = (string)$activeTheme;
$activeThemeName = (string)$activeThemeName;
$previewUrl = trim((string)$previewUrl);
$previewBase = $previewUrl !== '' ? $previewUrl : $absoluteUrl('');

$sectionLabel = static function (string $key, array $section): string {
    $label = trim((string)$section['label']);
    return $label !== '' ? $label : $key;
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
                                <?php $fieldValue = (string)($values[$fieldKey] ?? $field['default']); ?>
                                <?php require BASE_DIR . '/' . VIEW_DIR . 'admin/themes/field.php'; ?>
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
