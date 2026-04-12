<?php
$posts = is_array($posts ?? null) ? $posts : [];
$loopTitle = (string)($loopTitle ?? $t('front.home.published_posts'));
?>
<section class="theme-post-loop">
    <h2 class="theme-section-title"><?= htmlspecialchars($loopTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($posts === []): ?>
        <p class="theme-muted"><?= htmlspecialchars((string)($emptyMessage ?? $t('front.home.no_posts')), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <div class="theme-post-grid">
            <?php foreach ($posts as $post): ?>
                <?php
                $postThumb = (array)($post['thumbnail'] ?? []);
                $thumbSrc = $website_thumbnail($postThumb, '640');
                $postName = (string)($post['name'] ?? '');
                ?>
                <article class="theme-post-card">
                    <a class="theme-post-link" href="<?= htmlspecialchars($url((string)($post['url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($thumbSrc !== ''): ?>
                            <picture class="theme-post-thumb">
                                <?php if ((string)($postThumb['webp'] ?? '') !== ''): ?>
                                    <source type="image/webp" srcset="<?= htmlspecialchars($website_thumbnail_srcset($postThumb), ENT_QUOTES, 'UTF-8') ?>" sizes="(max-width: 900px) 100vw, 33vw">
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($thumbSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($postName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                            </picture>
                        <?php endif; ?>
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
</section>
