<div class="container py-5">
    <div class="row">
        <div class="col-8">
            <div class="card p-5 bg-light mb-4">
                <h1 class="m-0 mb-3">TinyCMS</h1>
                <p class="m-0 mb-4 text-muted">Minimalistické CMS bez balastu.</p>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>">Login</a>
                    <?php if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
                    <a class="btn btn-dark" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($user)): ?>
            <div class="card p-4">
                <p class="m-0 mb-2"><strong>Uživatel:</strong> <?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="m-0 mb-2"><strong>Email:</strong> <?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="m-0"><strong>Role:</strong> <?= htmlspecialchars((string)($user['role'] ?? 'guest'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
