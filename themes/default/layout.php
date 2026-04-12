<?php
declare(strict_types=1);

$activeTheme = trim((string)($themeName ?? 'default'));
$themeCss = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/css/style.css' : 'themes/default/assets/css/style.css';
$themeJs = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/js/theme.js' : 'themes/default/assets/js/theme.js';
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?= $metaHead() ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($url($themeCss), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url($themeJs), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body class="theme-<?= htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') ?>">
<div class="site-shell">
    <header class="site-header">
        <a class="site-brand" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </header>
    <div class="site-layout">
        <aside class="site-sidebar">
            <form class="site-search" method="get" action="<?= htmlspecialchars($url('search'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="search" name="q" placeholder="<?= htmlspecialchars($t('front.search.placeholder'), ENT_QUOTES, 'UTF-8') ?>" required>
                <button type="submit"><?= htmlspecialchars($t('front.search.submit'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <nav class="site-nav">
                <a href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.home.published_posts'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="<?= htmlspecialchars($url('search'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.search.title'), ENT_QUOTES, 'UTF-8') ?></a>
                <?php if (!empty($user) && (string)($user['role'] ?? '') === 'admin'): ?>
                    <a href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('admin.menu.dashboard'), ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title'), ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </nav>
        </aside>
        <main id="main-content" class="site-main">
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
