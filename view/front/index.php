<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card p-5 bg-light mb-4">
                <h1 class="m-0 mb-3"><?= $escape($site_title()) ?></h1>
                <p class="m-0 mb-2 text-muted">Minimalistické CMS bez balastu.</p>
                <p class="m-0 mb-4 text-muted">Autor: <?= $escape((string)($siteAuthor ?? 'Admin')) ?></p>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= $escape($url('login')) ?>">Login</a>
                    <?php if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
                    <a class="btn btn-light" href="<?= $escape($url('admin/dashboard')) ?>">Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($user)): ?>
            <div class="card p-4">
                <p class="m-0 mb-2"><strong>Uživatel:</strong> <?= $escape((string)($user['name'] ?? '')) ?></p>
                <p class="m-0 mb-2"><strong>Email:</strong> <?= $escape((string)($user['email'] ?? '')) ?></p>
                <p class="m-0"><strong>Role:</strong> <?= $escape((string)($user['role'] ?? 'guest')) ?></p>
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
                            <a href="<?= $escape($url((string)($post['url'] ?? ''))) ?>"><?= $escape((string)($post['name'] ?? '')) ?></a>
                        </h3>
                        <p class="m-0 mb-1 text-muted"><?= $escape($date((string)($post['created'] ?? '')) . ' ' . $time((string)($post['created'] ?? ''))) ?></p>
                        <p class="m-0 mb-1"><small>Slug URL: /<?= $escape((string)($post['url'] ?? '')) ?></small></p>
                        <p class="m-0"><small>Short URL: /<?= $escape((string)($post['type_slug'] ?? '')) ?>/<?= $escape((string)($post['id'] ?? '')) ?></small></p>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
