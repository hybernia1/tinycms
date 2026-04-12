<?php
$posts = is_array($posts ?? null) ? $posts : [];
$loopTitle = (string)($loopTitle ?? $t('front.home.published_posts'));
?>
<div class="theme-post-loop">
    <h2 class="theme-section-title"><?= htmlspecialchars($loopTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($posts === []): ?>
        <p class="theme-muted"><?= htmlspecialchars((string)($emptyMessage ?? $t('front.home.no_posts')), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <div class="theme-post-grid">
            <?php foreach ($posts as $post): ?>
                <?php
                $postThumb = (array)($post['thumbnail'] ?? []);
                $postName = (string)($post['name'] ?? '');
                ?>
                <article class="theme-post-card">
                    <a class="theme-post-link" href="<?= htmlspecialchars($url((string)($post['url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                        <?= $renderPicture($postThumb, $postName, ['class' => 'theme-post-thumb', 'sizes' => '(max-width: 900px) 100vw, 33vw']) ?>
                        <div class="theme-post-content">
                            <h3><?= htmlspecialchars($postName, ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="theme-post-date"><?= htmlspecialchars($formatDateTime((string)($post['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ((string)($post['excerpt'] ?? '') !== ''): ?>
                                <p class="theme-post-excerpt"><?= htmlspecialchars((string)$post['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <span class="theme-post-more"><?= htmlspecialchars($t('front.home.read_more'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
