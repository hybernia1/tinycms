<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= $escHtml($lang) ?>">
<head>
    <?= $head ?>
    <link rel="stylesheet" href="<?= $escUrl($themeUrl('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="<?= $escUrl($url('')) ?>" class="site-title">
            <?php if (trim($theme->siteLogo()) !== ''): ?>
                <img src="<?= $escUrl($url($theme->siteLogo())) ?>" alt="<?= $escHtml($theme->siteTitle()) ?>" class="site-logo">
            <?php endif; ?>
            <span><?= $escHtml($theme->siteTitle()) ?></span>
        </a>
        <?= $menu() ?>
        <?= $searchForm('search', (string)($_GET['q'] ?? '')) ?>
    </div>
</header>
<main class="container">
    <?= $content ?>
</main>
<script src="<?= $escUrl($themeUrl('assets/js/main.js')) ?>" defer></script>
</body>
</html>
