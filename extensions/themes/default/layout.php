<?php

if (!defined('BASE_DIR')) {
    exit;
}

$leftSidebar = get_widgets('left');
$rightSidebar = get_widgets('right');
$layoutClass = trim('container site-layout'
    . ($leftSidebar !== '' ? ' has-left-sidebar' : '')
    . ($rightSidebar !== '' ? ' has-right-sidebar' : ''));

?>
<!doctype html>
<html lang="<?= esc_attr(site_language()) ?>">
<head>
    <?= get_head() . PHP_EOL ?>
</head>
<body>
    <?php do_action('theme_body_open') ?>
    <header class="site-header">
        <div class="container">
            <a href="<?= esc_url(site_url()) ?>" class="site-title">
                <?= site_logo() ?>
                <span><?= esc_html(site_title()) ?></span>
            </a>
            <?= get_menu() ?>
            <?= get_search_form() ?>
        </div>
    </header>
    <?php do_action('theme_header_after') ?>
    <main class="<?= esc_attr($layoutClass) ?>">
        <?php if ($leftSidebar !== ''): ?>
            <aside class="theme-sidebar theme-sidebar-left" aria-label="<?= esc_attr(t('theme.sidebar.left', 'Left sidebar')) ?>">
                <?= $leftSidebar ?>
            </aside>
        <?php endif; ?>
        <div class="site-content">
            <?php do_action('theme_content_before') ?>
            <?= $content ?>
            <?php do_action('theme_content_after') ?>
        </div>
        <?php if ($rightSidebar !== ''): ?>
            <aside class="theme-sidebar theme-sidebar-right" aria-label="<?= esc_attr(t('theme.sidebar.right', 'Right sidebar')) ?>">
                <?= $rightSidebar ?>
            </aside>
        <?php endif; ?>
    </main>
    <?php do_action('theme_footer_before') ?>
    <footer class="site-footer">
        <div class="container">
            <p><?= get_footer(); ?></p>
        </div>
    </footer>
    <script src="<?= esc_url(theme_url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
