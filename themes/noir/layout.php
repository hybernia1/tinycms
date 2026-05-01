<?php

if (!defined('BASE_DIR')) {
    exit;
}

$widgetsEnabled = widgets_enabled();
$searchEnabled = search_enabled();
$layoutWidth = layout_width();
$brandDisplay = site_brand_display();
$brandLogo = in_array($brandDisplay, ['both', 'logo'], true) ? site_logo() : '';
$brandTitle = in_array($brandDisplay, ['both', 'title'], true);
$hasBrand = $brandLogo !== '' || $brandTitle;
$rightSidebar = $widgetsEnabled ? get_widget_area('right') : '';
?>
<!doctype html>
<html lang="<?= esc_attr(site_language()) ?>">
<head>
    <?= get_head() . PHP_EOL ?>
</head>
<body class="theme-layout-<?= esc_attr($layoutWidth) ?>">
    <header class="site-header">
        <div class="container site-header-inner">
            <?php if ($hasBrand): ?>
            <a href="<?= esc_url(site_url()) ?>" class="site-title">
                <?= $brandLogo ?>
                <?php if ($brandTitle): ?>
                <span><?= esc_html(site_title()) ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <div class="site-desktop-nav">
                <?= get_menu() ?>
            </div>
            <?php if ($searchEnabled): ?>
            <?= get_search_form() ?>
            <?php endif; ?>
            <button class="site-menu-toggle" type="button" aria-label="Menu" aria-expanded="false" data-menu-toggle>
                <?= icon('menu') ?>
            </button>
        </div>
    </header>
    <div class="site-nav-panel" data-menu-panel>
        <div class="site-nav-panel-header">
            <span><?= esc_html(site_title()) ?></span>
            <button type="button" aria-label="Close menu" data-menu-close><?= icon('cancel') ?></button>
        </div>
        <?= get_menu(['class' => 'site-menu site-menu-mobile']) ?>
    </div>
    <button class="site-nav-backdrop" type="button" aria-label="Close menu" data-menu-close></button>
    <main class="container site-main<?= $rightSidebar !== '' ? ' has-sidebar' : '' ?>">
        <div class="site-content"><?= $content ?></div>
        <?php if ($rightSidebar !== ''): ?>
        <aside class="site-sidebar"><?= $rightSidebar ?></aside>
        <?php endif; ?>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p><?= get_footer(); ?></p>
        </div>
    </footer>
    <script src="<?= esc_url(theme_url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
