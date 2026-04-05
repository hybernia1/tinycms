<?php
$posts = is_array($posts ?? null) ? $posts : [];
$loopTitle = (string)($loopTitle ?? $t('front.home.published_posts', 'Published posts'));
?>
<div class="theme-post-loop">
    <h2 class="theme-section-title"><?= htmlspecialchars($loopTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($posts === []): ?>
        <p class="theme-muted"><?= htmlspecialchars((string)($emptyMessage ?? $t('front.home.no_posts', 'No published posts yet.')), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <div class="theme-post-grid">
            <?php foreach ($posts as $post): ?>
                <?php
                $postThumb = (array)($post['thumbnail'] ?? []);
                $thumbWebp = trim((string)($postThumb['webp'] ?? ''));
                $thumbPath = trim((string)($postThumb['path'] ?? ''));
                $thumbSources = (array)($postThumb['webp_sources'] ?? []);
                $postName = (string)($post['name'] ?? '');
                ?>
                <article class="theme-post-card">
                    <a class="theme-post-link" href="<?= htmlspecialchars($url((string)($post['url'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($thumbWebp !== '' || $thumbPath !== ''): ?>
                            <picture class="theme-post-thumb">
                                <?php if ($thumbWebp !== ''): ?>
                                    <?php
                                    $srcsetParts = [];
                                    foreach ($thumbSources as $source) {
                                        $sourcePath = trim((string)($source['path'] ?? ''));
                                        $sourceWidth = (int)($source['width'] ?? 0);
                                        if ($sourcePath === '' || $sourceWidth <= 0) {
                                            continue;
                                        }
                                        $srcsetParts[] = $url($sourcePath) . ' ' . $sourceWidth . 'w';
                                    }
                                    $srcset = $srcsetParts !== [] ? implode(', ', $srcsetParts) : $url($thumbWebp);
                                    ?>
                                    <source type="image/webp" srcset="<?= htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') ?>" sizes="(max-width: 900px) 100vw, 33vw">
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($url($thumbPath !== '' ? $thumbPath : $thumbWebp), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($postName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                            </picture>
                        <?php endif; ?>
                        <div class="theme-post-content">
                            <h3><?= htmlspecialchars($postName, ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="theme-post-date"><?= htmlspecialchars($formatDateTime((string)($post['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ((string)($post['excerpt'] ?? '') !== ''): ?>
                                <p class="theme-post-excerpt"><?= htmlspecialchars((string)$post['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <span class="theme-post-more"><?= htmlspecialchars($t('front.home.read_more', 'Read more'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
