<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card p-5 bg-light mb-4">
                <h1 class="m-0 mb-3"><?= $e((string)($siteName ?? 'TinyCMS')) ?></h1>
                <p class="m-0 mb-2 text-muted">Minimalistické CMS bez balastu.</p>
                <p class="m-0 mb-4 text-muted">Autor: <?= $e((string)($siteAuthor ?? 'Admin')) ?></p>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= $e($url('login')) ?>">Login</a>
                    <?php if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
                    <a class="btn btn-light" href="<?= $e($url('admin/dashboard')) ?>">Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($user)): ?>
            <div class="card p-4">
                <p class="m-0 mb-2"><strong>Uživatel:</strong> <?= $e((string)($user['name'] ?? '')) ?></p>
                <p class="m-0 mb-2"><strong>Email:</strong> <?= $e((string)($user['email'] ?? '')) ?></p>
                <p class="m-0"><strong>Role:</strong> <?= $e((string)($user['role'] ?? 'guest')) ?></p>
            </div>
            <?php endif; ?>
            <div class="card p-4 mt-4">
                <h2 class="h4 mb-3">Publikované články</h2>
                <?php if (empty($posts)): ?>
                <p class="m-0 text-muted">Zatím nejsou žádné publikované články.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($posts as $post): ?>
                    <li class="list-group-item px-0">
                        <h3 class="h6 m-0 mb-1">
                            <a href="<?= $e($url((string)($post['url'] ?? ''))) ?>"><?= $e((string)($post['name'] ?? '')) ?></a>
                        </h3>
                        <p class="m-0 mb-1 text-muted"><?= $e($d((string)($post['created'] ?? '')) . ' ' . $t((string)($post['created'] ?? ''))) ?></p>
                        <p class="m-0 mb-1"><small>Slug URL: /<?= $e((string)($post['url'] ?? '')) ?></small></p>
                        <p class="m-0"><small>Short URL: /<?= $e((string)($post['type_slug'] ?? '')) ?>/<?= $e((string)($post['id'] ?? '')) ?></small></p>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
