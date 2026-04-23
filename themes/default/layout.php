<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= esc_attr(site_language()) ?>">
<head>
    <?= get_head() ?>
    <link rel="stylesheet" href="<?= esc_url(theme_url('assets/css/style.css')) ?>">
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
<main class="container">
    <?= $content ?>
</main>
<?php $footerText = get_footer(); ?>
<?php if ($footerText !== ''): ?>
<footer class="site-footer">
    <div class="container">
        <p><?= esc_html($footerText) ?></p>
    </div>
</footer>
<?php endif; ?>
<script src="<?= esc_url(theme_url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
