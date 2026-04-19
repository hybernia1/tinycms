<!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?></title>
    <meta name="description" content="<?= $e($setting('meta_description')) ?>">
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
    </div>
</header>
<main class="container">
    <?= $content ?>
</main>
<script src="<?= $e($themeUrl('assets/js/main.js')) ?>" defer></script>
</body>
</html>
