<div class="card p-4">
    <p class="m-0 text-muted"><?= esc_html(t('admin.dashboard.logged_in')) ?>: <?= esc_html((string)($user['name'] ?? t('users.user'))) ?></p>
</div>
