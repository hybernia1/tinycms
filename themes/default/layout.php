<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
    <?= $head ?>
    <link rel="stylesheet" href="<?= $e($themeUrl('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="<?= $e($url('')) ?>" class="site-title">
            <?php if (trim($theme->siteLogo()) !== ''): ?>
                <img src="<?= $e($url($theme->siteLogo())) ?>" alt="<?= $e($theme->siteTitle()) ?>" class="site-logo">
            <?php endif; ?>
            <span><?= $e($theme->siteTitle()) ?></span>
        </a>
        <?= $searchForm('search', (string)($_GET['q'] ?? '')) ?>
    </div>
</header>
<main class="container">
    <?= $content ?>
</main>
<script src="<?= $e($themeUrl('assets/js/main.js')) ?>" defer></script>
</body>
</html>
