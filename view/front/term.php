<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-8">
            <article class="card p-4 mb-4">
                <h1 class="mb-3"><?= htmlspecialchars($t('front.term.title', 'Tag'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string)($term['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if ((string)($term['body'] ?? '') !== ''): ?>
                <p class="m-0"><?= nl2br(htmlspecialchars((string)($term['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </article>
            <div class="card p-4">
                <h2 class="mb-3"><?= htmlspecialchars($t('front.home.published_posts', 'Published posts'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (empty($posts)): ?>
                <p class="m-0 text-muted"><?= htmlspecialchars($t('front.term.no_posts', 'There are no published posts for this tag yet.'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                <ul class="m-0 mb-3">
                    <?php foreach ($posts as $post): ?>
                    <li>
                        <h3 class="m-0 mb-1">
                            <a href="<?= htmlspecialchars($url((string)($post['url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($post['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        </h3>
                        <p class="m-0 text-muted"><?= htmlspecialchars($formatDateTime((string)($post['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php
                $currentPage = (int)($pagination['page'] ?? 1);
                $totalPages = (int)($pagination['total_pages'] ?? 1);
                $termSlug = (string)($term['slug'] ?? '');
                ?>
                <?php if ($totalPages > 1): ?>
                <nav class="d-flex gap-2">
                    <?php if ($currentPage > 1): ?>
                    <a class="btn btn-light" href="<?= htmlspecialchars($url('term/' . $termSlug . ($currentPage - 1 > 1 ? '?page=' . ($currentPage - 1) : '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                    <a class="btn btn-light" href="<?= htmlspecialchars($url('term/' . $termSlug . '?page=' . ($currentPage + 1)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
