<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= esc_attr($lang) ?>">
<head>
    <?= $head ?>
    <link rel="stylesheet" href="<?= esc_url($themeUrl('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="<?= esc_url($url('')) ?>" class="site-title">
            <?php if (trim($theme->siteLogo()) !== ''): ?>
                <img src="<?= esc_url($url($theme->siteLogo())) ?>" alt="<?= esc_attr($theme->siteTitle()) ?>" class="site-logo">
            <?php endif; ?>
            <span><?= esc_html($theme->siteTitle()) ?></span>
        </a>
        <?= $menu() ?>
        <?= $searchForm('search', (string)($_GET['q'] ?? '')) ?>
    </div>
</header>
<main class="container">
    <?= $content ?>
</main>
<script src="<?= esc_url($themeUrl('assets/js/main.js')) ?>" defer></script>
</body>
</html>
