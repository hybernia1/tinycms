<div class="container py-5">
    <article class="theme-detail card p-4">
        <h1><?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="theme-post-date"><?= htmlspecialchars($formatDateTime((string)($item['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>


        <?php $terms = (array)($item['terms'] ?? []); ?>
        <?php if ($terms !== []): ?>
            <div class="theme-tags mb-3">
                <?php foreach ($terms as $term): ?>
                    <a class="theme-tag" href="<?= htmlspecialchars($url('term/' . (string)($term['slug'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">#<?= htmlspecialchars((string)($term['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        $thumb = (array)($item['thumbnail'] ?? []);
        $thumbWebp = trim((string)($thumb['webp'] ?? ''));
        $thumbPath = trim((string)($thumb['path'] ?? ''));
        $thumbSources = (array)($thumb['webp_sources'] ?? []);
        ?>
        <?php if ($thumbWebp !== '' || $thumbPath !== ''): ?>
            <picture class="theme-detail-thumb mb-4">
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
                    <source type="image/webp" srcset="<?= htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') ?>" sizes="(max-width: 900px) 100vw, 900px">
                <?php endif; ?>
                <img src="<?= htmlspecialchars($url($thumbPath !== '' ? $thumbPath : $thumbWebp), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" loading="eager" decoding="async">
            </picture>
        <?php endif; ?>

        <?php if ((string)($item['excerpt'] ?? '') !== ''): ?>
            <p class="theme-excerpt"><?= htmlspecialchars((string)$item['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="theme-content">
            <?= (string)($item['body'] ?? '') ?>
        </div>
    </article>
</div>
