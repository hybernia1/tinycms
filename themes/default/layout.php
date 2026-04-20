<!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
    <?= $head ?>
    <?php $siteFavicon = trim($setting('favicon')); ?>
    <?php if ($siteFavicon !== ''): ?>
        <link rel="icon" href="<?= $e($url($siteFavicon)) ?>">
    <?php endif; ?>
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
<?php if (($kind ?? '') === 'comments'): ?>
    <script src="<?= $e($url(ASSETS_DIR . 'js/i18n.js')) ?>" defer></script>
    <script src="<?= $e($url(ASSETS_DIR . 'js/api.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
