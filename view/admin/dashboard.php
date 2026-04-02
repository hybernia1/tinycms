<div class="card p-5 bg-light">
    <h1 class="m-0 mb-3">Admin dashboard</h1>
    <p class="m-0 text-muted">Přihlášen: <?= htmlspecialchars((string)($user['name'] ?? 'Uživatel'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
