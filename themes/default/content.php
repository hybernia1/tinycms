<div class="theme-page">
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

        <?php
        $thumb = (array)($item['thumbnail'] ?? []);
        ?>
        <?= $renderPicture($thumb, (string)($item['name'] ?? ''), ['class' => 'theme-detail-thumb', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>

        <?php if ((string)($item['excerpt'] ?? '') !== ''): ?>
            <p class="theme-excerpt"><?= htmlspecialchars((string)$item['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="theme-content">
            <?= (string)($item['body'] ?? '') ?>
        </div>
    </article>
</div>
