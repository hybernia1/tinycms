<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?= $metaHead() ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('themes/default/assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url('themes/default/assets/js/theme.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body class="theme-default">
<div class="site-shell">
    <header class="site-header">
        <a class="site-brand" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>">
            <?php if ((string)($siteLogo ?? '') !== ''): ?>
                <img class="site-brand-logo" src="<?= htmlspecialchars($url((string)$siteLogo), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
                <?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
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
            </nav>
        </aside>
        <main id="main-content" class="site-main">
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
