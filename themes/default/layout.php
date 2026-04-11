<?php
declare(strict_types=1);

$activeTheme = trim((string)($themeName ?? 'default'));
$themeCss = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/css/style.css' : 'themes/default/assets/css/style.css';
$themeJs = $activeTheme !== '' ? 'themes/' . $activeTheme . '/assets/js/theme.js' : 'themes/default/assets/js/theme.js';
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
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url($themeCss), ENT_QUOTES, 'UTF-8') ?>">
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
<main id="main-content">
<?= $content ?>
</main>
</body>
</html>
