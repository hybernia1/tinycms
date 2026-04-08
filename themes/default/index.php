<div class="container py-5">
    <section class="theme-hero card p-5 mb-4">
        <h1><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="theme-muted mb-2"><?= htmlspecialchars($t('front.home.tagline'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="theme-muted mb-4"><?= htmlspecialchars($t('front.home.author'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($siteAuthor ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php if (!empty($user) && in_array((string)($user['role'] ?? ''), ['admin', 'editor'], true)): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('admin.menu.dashboard'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $loopTitle = $t('front.home.published_posts');
    $emptyMessage = $t('front.home.no_posts');
    require __DIR__ . '/parts/post-loop.php';
    ?>
</div>
