<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= esc_attr(site_language()) ?>">
<head>
    <?= get_head() . PHP_EOL ?>
</head>
<body>
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
    <main class="container site-main">
        <section class="site-widgets site-widgets-before"><?= get_widget_area('before_content') ?></section>
        <aside class="site-widgets site-widgets-left"><?= get_widget_area('left') ?></aside>
        <div class="site-content"><?= $content ?></div>
        <aside class="site-widgets site-widgets-right"><?= get_widget_area('right') ?></aside>
        <section class="site-widgets site-widgets-after"><?= get_widget_area('after_content') ?></section>
    </main>
    <footer class="site-footer">
        <div class="container">
            <p><?= get_footer(); ?></p>
        </div>
    </footer>
    <script src="<?= esc_url(theme_url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
