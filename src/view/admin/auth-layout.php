<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

$scripts = ['core.js', 'api.js', 'admin-ui/orchestrator.js'];
?>
<!doctype html>
<html lang="<?= esc_attr((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= esc_html((string)$pageTitle) ?> | <?= esc_html(t('admin.title_suffix')) ?></title>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= esc_url($url((string)$siteFavicon)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= esc_url($url(ASSETS_DIR . 'css/style.css')) ?>">
    <link rel="stylesheet" href="<?= esc_url($url(ASSETS_DIR . 'css/admin.css')) ?>">
    <script>window.tinycmsI18n = <?= esc_json($adminI18n ?? []) ?>;</script>
    <script>window.tinycmsIconSprite = <?= esc_json(icon_sprite()) ?>;</script>
    <?php foreach ($scripts as $script): ?>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/' . $script)) ?>"></script>
    <?php endforeach; ?>
</head>
<body>
<?= $content ?>
</body>
</html>
