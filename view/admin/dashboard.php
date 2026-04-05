<div class="card p-5">
    <p class="m-0 text-muted"><?= htmlspecialchars($t('admin.dashboard.logged_in', 'Logged in'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($user['name'] ?? $t('front.home.user', 'User')), ENT_QUOTES, 'UTF-8') ?></p>
</div>
