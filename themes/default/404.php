<div class="theme-page">
    <section class="theme-panel">
        <h1><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="theme-muted"><?= htmlspecialchars($t('front.not_found.title'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="theme-muted"><?= htmlspecialchars($t('front.not_found.page'), ENT_QUOTES, 'UTF-8') ?></p>
        <a class="theme-link-button" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.not_found.back_home'), ENT_QUOTES, 'UTF-8') ?></a>
    </section>
</div>
