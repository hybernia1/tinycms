<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card p-5 mb-4">
                <h1 class="m-0 mb-3"><?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="m-0 mb-2 text-muted"><?= htmlspecialchars($t('front.home.tagline', 'Minimal CMS without bloat.'), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="m-0 mb-4 text-muted"><?= htmlspecialchars($t('front.home.author', 'Author'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($siteAuthor ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title', 'Login'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
                    <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('admin.menu.dashboard', 'Dashboard'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($user)): ?>
            <div class="card p-4">
                <p class="m-0 mb-2"><strong><?= htmlspecialchars($t('front.home.user', 'User'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="m-0 mb-2"><strong>Email:</strong> <?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p class="m-0"><strong><?= htmlspecialchars($t('front.home.role', 'Role'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string)($user['role'] ?? 'guest'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php endif; ?>
            <div class="card p-4 mt-4">
                <h2 class="mb-3"><?= htmlspecialchars($t('front.home.published_posts', 'Published posts'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (empty($posts)): ?>
                <p class="m-0 text-muted"><?= htmlspecialchars($t('front.home.no_posts', 'No published posts yet.'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                <ul class="m-0">
                    <?php foreach ($posts as $post): ?>
                    <li>
                        <h3 class="m-0 mb-1">
                            <a href="<?= htmlspecialchars($url((string)($post['url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($post['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        </h3>
                        <p class="m-0 mb-1 text-muted"><?= htmlspecialchars($formatDateTime((string)($post['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="m-0 mb-1"><small><?= htmlspecialchars($t('front.home.slug_url', 'Slug URL'), ENT_QUOTES, 'UTF-8') ?>: /<?= htmlspecialchars((string)($post['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></p>
                        <p class="m-0"><small><?= htmlspecialchars($t('front.home.short_url', 'Short URL'), ENT_QUOTES, 'UTF-8') ?>: /<?= htmlspecialchars((string)($post['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></p>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
