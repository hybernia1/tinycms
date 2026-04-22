<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= $escHtml((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= $escHtml((string)$pageTitle) ?> | <?= $escHtml($t('admin.title_suffix')) ?></title>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= $escUrl($url((string)$siteFavicon)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $escUrl($url(ASSETS_DIR . 'css/style.css')) ?>">
    <link rel="stylesheet" href="<?= $escUrl($url(ASSETS_DIR . 'css/admin.css')) ?>">
    <script>window.tinycmsI18n = <?= json_encode($adminI18n ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/i18n.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/api.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/password-toggle.js')) ?>"></script>
</head>
<body>
<?= $content ?>
</body>
</html>
