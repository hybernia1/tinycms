<div class="card p-4">
    <p class="m-0 text-muted"><?= $escHtml($t('admin.dashboard.logged_in')) ?>: <?= $escHtml((string)($user['name'] ?? $t('users.user'))) ?></p>
</div>
