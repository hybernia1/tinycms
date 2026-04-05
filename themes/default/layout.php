<?php
declare(strict_types=1);

$activeTheme = trim((string)($themeName ?? 'default'));
$themeCss = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/css/style.css' : 'themes/default/assets/css/style.css';
$themeJs = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/js/theme.js' : 'themes/default/assets/js/theme.js';
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?= $renderFrontHead([
        'title' => (string)($metaTitle ?? $pageTitle ?? 'TinyCMS'),
        'description' => (string)($metaDescription ?? ''),
        'robots' => (string)($metaRobots ?? 'index,follow'),
    ]) ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url($themeCss), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url('assets/js/password-toggle.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url($themeJs), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body class="theme-<?= htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') ?>">
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
    <div class="flash flash-<?= htmlspecialchars((string)($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
        <span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" data-flash-close aria-label="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $icon('cancel') ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?= $content ?>
<footer class="container py-4 text-muted">
    <?= htmlspecialchars((string)($siteFooter ?? '© TinyCMS'), ENT_QUOTES, 'UTF-8') ?>
</footer>
</body>
</html>
