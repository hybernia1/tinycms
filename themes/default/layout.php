<?php
declare(strict_types=1);

$activeTheme = trim((string)($themeName ?? 'default'));
$themeCss = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/css/style.css' : 'themes/default/assets/css/style.css';
$themeJs = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/js/theme.js' : 'themes/default/assets/js/theme.js';
$searchValue = trim((string)($query ?? ''));
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?php
    $metaPath = trim((string)($metaPath ?? ''));
    $shortlinkPath = trim((string)($shortlinkPath ?? ''));
    $alternateLinks = [];
    foreach ((array)($metaAlternateLinks ?? []) as $link) {
        if (!is_array($link)) {
            continue;
        }
        $href = trim((string)($link['href'] ?? ''));
        if ($href === '') {
            continue;
        }
        $link['href'] = $absoluteUrl($href);
        $alternateLinks[] = $link;
    }
    ?>
    <?= $renderFrontHead([
        'title' => (string)($metaTitle ?? $pageTitle ?? 'TinyCMS'),
        'description' => (string)($metaDescription ?? ''),
        'keywords' => (array)($metaKeywords ?? []),
        'robots' => (string)($metaRobots ?? 'index,follow'),
        'url' => $metaPath !== '' ? $absoluteUrl($metaPath) : '',
        'shortlink' => $shortlinkPath !== '' ? $absoluteUrl($shortlinkPath) : '',
        'og_type' => (string)($metaOgType ?? 'website'),
        'og_image' => (string)($metaOgImage ?? '') !== ''
            ? $absoluteUrl((string)$metaOgImage)
            : ((string)($siteLogo ?? '') !== '' ? $absoluteUrl((string)$siteLogo) : ''),
        'site_name' => (string)($siteName ?? 'TinyCMS'),
        'author' => (string)($siteAuthor ?? ''),
        'theme_color' => (string)($metaThemeColor ?? '#2563eb'),
        'favicon' => (string)($siteFavicon ?? '') !== '' ? $absoluteUrl((string)$siteFavicon) : '',
        'logo' => (string)($siteLogo ?? '') !== '' ? $absoluteUrl((string)$siteLogo) : '',
        'structured_data' => $metaStructuredData ?? null,
        'published_time' => (string)($metaPublishedTime ?? ''),
        'modified_time' => (string)($metaModifiedTime ?? ''),
        'search_url_template' => isset($metaSearchUrlTemplate) ? $absoluteUrl((string)$metaSearchUrlTemplate) : '',
        'alternate_links' => $alternateLinks,
    ]) ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($url($themeCss), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url($themeJs), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body class="theme-<?= htmlspecialchars($activeTheme, ENT_QUOTES, 'UTF-8') ?>">
<header class="theme-header">
    <div class="theme-shell theme-header-inner">
        <a class="theme-brand" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>">
            <?php if ((string)($siteLogo ?? '') !== ''): ?>
                <img src="<?= htmlspecialchars($url((string)$siteLogo), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
                <span><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </a>
        <form method="get" action="<?= htmlspecialchars($url('search'), ENT_QUOTES, 'UTF-8') ?>" class="theme-search-form">
            <input type="search" name="q" value="<?= htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('front.search.placeholder'), ENT_QUOTES, 'UTF-8') ?>" required>
            <button type="submit"><?= htmlspecialchars($t('front.search.submit'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
</header>
<?php if ($flashes !== []): ?>
<div class="theme-shell theme-flashes">
    <?php foreach ($flashes as $flash): ?>
        <div class="theme-flash theme-flash-<?= htmlspecialchars((string)($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
            <span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" data-flash-close aria-label="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('cancel') ?>
            </button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<main id="main-content" class="theme-shell theme-layout-grid">
    <section class="theme-main-content">
        <?= $content ?>
    </section>
    <aside class="theme-sidebar">
        <div class="theme-panel">
            <h2><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($t('front.home.tagline'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </aside>
</main>
</body>
</html>
