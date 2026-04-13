<main class="container py-4">
    <section class="theme-hero card p-5 mb-4">
        <h1><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="theme-muted mb-2"><?= htmlspecialchars($t('front.not_found.title'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="theme-muted mb-4"><?= htmlspecialchars($t('front.not_found.page'), ENT_QUOTES, 'UTF-8') ?></p>
        <a class="btn btn-primary" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.not_found.back_home'), ENT_QUOTES, 'UTF-8') ?></a>
    </section>
</main>
