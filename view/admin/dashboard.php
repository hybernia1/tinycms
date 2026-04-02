<div class="container py-5">
    <div class="row">
        <div class="col-8">
            <div class="card p-5 bg-light">
                <div class="d-flex justify-between align-center mb-4">
                    <h1 class="m-0">Admin dashboard</h1>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users'), ENT_QUOTES, 'UTF-8') ?>">Uživatelé</a>
                        <a class="btn btn-dark" href="<?= htmlspecialchars($url('admin/logout'), ENT_QUOTES, 'UTF-8') ?>">Odhlásit</a>
                    </div>
                </div>
                <p class="m-0 text-muted">Přihlášen: <?= htmlspecialchars((string)($user['name'] ?? 'Uživatel'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>
