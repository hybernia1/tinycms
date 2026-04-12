<section class="theme-hero">
    <h1><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="theme-muted"><?= htmlspecialchars($t('front.home.author'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($siteAuthor ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="theme-actions">
        <a class="theme-btn theme-btn-primary" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php if (!empty($user) && (string)($user['role'] ?? '') === 'admin'): ?>
            <a class="theme-btn" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('admin.menu.dashboard'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </div>
</section>

<?php
$loopTitle = $t('front.home.published_posts');
$emptyMessage = $t('front.home.no_posts');
require __DIR__ . '/parts/post-loop.php';
require __DIR__ . '/parts/pagination.php';
?>
