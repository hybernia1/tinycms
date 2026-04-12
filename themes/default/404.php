<div class="container py-5">
    <section class="theme-hero card p-5">
        <p class="theme-muted mb-2">404</p>
        <h1><?= htmlspecialchars($t('front.not_found.page'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="theme-muted mb-4"><?= htmlspecialchars($t('front.not_found.detail'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php if ((string)($requestPath ?? '') !== ''): ?>
            <span class="btn btn-light"><?= htmlspecialchars((string)$requestPath, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </section>
</div>
