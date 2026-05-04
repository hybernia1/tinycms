<?php

if (!defined('BASE_DIR')) {
    exit;
}

$brand = get_site_brand();
$desktopMenu = get_menu();
$mobileMenu = get_menu(['class' => 'site-menu site-menu-mobile']);
$hasMenu = $desktopMenu !== '' || $mobileMenu !== '';
$searchForm = get_search_form();
$footerEnabled = theme_option_enabled('enable_footer', true);
$widgetsBefore = get_widget_area('before_content');
$widgetsLeft = get_widget_area('left');
$widgetsRight = get_widget_area('right');
$widgetsAfter = get_widget_area('after_content');
?>
<!doctype html>
<html lang="<?= esc_attr(site_language()) ?>">
<head>
    <?= get_head() . PHP_EOL ?>
</head>
<body class="<?= esc_attr(site_layout_class()) ?>">
    <header class="site-header">
        <div class="container">
            <?= $brand ?>
            <?php if ($hasMenu): ?>
            <div class="site-desktop-nav">
                <?= $desktopMenu ?>
            </div>
            <?php endif; ?>
            <?= $searchForm ?>
            <?php if ($hasMenu): ?>
            <button class="site-menu-toggle" type="button" aria-label="Menu" aria-expanded="false" data-menu-toggle>
                <?= icon('menu') ?>
            </button>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($hasMenu): ?>
    <div class="site-nav-panel" data-menu-panel>
        <div class="site-nav-panel-header">
            <span><?= esc_html(site_title()) ?></span>
            <button type="button" aria-label="Close menu" data-menu-close><?= icon('cancel') ?></button>
        </div>
        <?= $mobileMenu ?>
    </div>
    <button class="site-nav-backdrop" type="button" aria-label="Close menu" data-menu-close></button>
    <?php endif; ?>
    <main class="container site-main">
        <?php if ($widgetsBefore !== ''): ?>
        <section class="site-widgets site-widgets-before"><?= $widgetsBefore ?></section>
        <?php endif; ?>
        <?php if ($widgetsLeft !== ''): ?>
        <aside class="site-widgets site-widgets-left"><?= $widgetsLeft ?></aside>
        <?php endif; ?>
        <div class="site-content"><?= $content ?></div>
        <?php if ($widgetsRight !== ''): ?>
        <aside class="site-widgets site-widgets-right"><?= $widgetsRight ?></aside>
        <?php endif; ?>
        <?php if ($widgetsAfter !== ''): ?>
        <section class="site-widgets site-widgets-after"><?= $widgetsAfter ?></section>
        <?php endif; ?>
    </main>
    <?php if ($footerEnabled): ?>
    <footer class="site-footer">
        <div class="container">
            <p><?= get_footer(); ?></p>
        </div>
    </footer>
    <?php endif; ?>
    <script src="<?= esc_url(theme_url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
