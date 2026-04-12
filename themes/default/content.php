<article class="theme-detail">
    <h1><?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="theme-post-date"><?= htmlspecialchars($formatDateTime((string)($item['created'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>

    <?php $terms = (array)($item['terms'] ?? []); ?>
    <?php if ($terms !== []): ?>
        <div class="theme-tags">
            <?php foreach ($terms as $term): ?>
                <a class="theme-tag" href="<?= htmlspecialchars($url('term/' . (string)($term['slug'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">#<?= htmlspecialchars((string)($term['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php $thumb = (array)($item['thumbnail'] ?? []); ?>
    <?php $thumbSrc = $website_thumbnail($thumb); ?>
    <?php if ($thumbSrc !== ''): ?>
        <picture class="theme-detail-thumb">
            <?php if ((string)($thumb['webp'] ?? '') !== ''): ?>
                <source type="image/webp" srcset="<?= htmlspecialchars($website_thumbnail_srcset($thumb), ENT_QUOTES, 'UTF-8') ?>" sizes="(max-width: 900px) 100vw, 900px">
            <?php endif; ?>
            <img src="<?= htmlspecialchars($thumbSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" loading="eager" fetchpriority="high" decoding="async">
        </picture>
    <?php endif; ?>

    <?php if ((string)($item['excerpt'] ?? '') !== ''): ?>
        <p class="theme-excerpt"><?= htmlspecialchars((string)$item['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="theme-content">
        <?= (string)($item['body'] ?? '') ?>
    </div>
</article>
