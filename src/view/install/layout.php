<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= esc_attr((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc_html((string)($pageTitle ?? 'TinyCMS')) ?></title>
    <link rel="stylesheet" href="<?= esc_url($url(ASSETS_DIR . 'css/style.css')) ?>">
    <script>window.tinycmsIconSprite = <?= esc_json(icon_sprite()) ?>;</script>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/icons.js')) ?>"></script>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/flash.js')) ?>"></script>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/modal.js')) ?>"></script>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/custom-select.js')) ?>"></script>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/password-toggle.js')) ?>"></script>
</head>
<body>
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
    <?php $flashType = (string)($flash['type'] ?? 'warning'); ?>
    <?php $flashIcon = $flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warning'); ?>
    <div class="flash flash-<?= esc_attr($flashType === 'info' ? 'warning' : $flashType) ?>">
        <span class="d-flex align-center gap-2"><?= icon($flashIcon) ?><span><?= esc_html((string)($flash['message'] ?? '')) ?></span></span>
        <button type="button" data-flash-close aria-label="<?= esc_attr(t('admin.close_notice')) ?>" title="<?= esc_attr(t('admin.close_notice')) ?>">
            <?= icon('cancel') ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?= $content ?>
</body>
</html>
