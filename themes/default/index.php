<div class="container py-5">
    <section class="theme-hero card p-5 mb-4">
        <?php $logo = trim((string)($siteLogo ?? '')); ?>
        <?php if ($logo !== ''): ?>
            <p class="mb-3"><img src="<?= htmlspecialchars($url($logo), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>" style="max-height:64px"></p>
        <?php endif; ?>
        <h1><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="theme-muted mb-2"><?= htmlspecialchars($t('front.home.tagline', 'Minimal CMS without bloat.'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="theme-muted mb-4"><?= htmlspecialchars($t('front.home.author', 'Author'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($siteAuthor ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title', 'Login'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php if ((bool)($allow_registration ?? true)): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('register'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.register.title', 'Register'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <?php if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('admin.menu.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $loopTitle = $t('front.home.published_posts', 'Published posts');
    $emptyMessage = $t('front.home.no_posts', 'No published posts yet.');
    require __DIR__ . '/parts/post-loop.php';
    ?>
</div>
