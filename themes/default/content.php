<article class="content-single">
    <h1><?= $e((string)($item['name'] ?? '')) ?></h1>
    <?php $meta = array_values(array_filter([$contentAuthor($item), $contentDate($item)])); ?>
    <?php if ($meta !== []): ?>
        <p class="text-muted small"><?= $e(implode(' · ', $meta)) ?></p>
    <?php endif; ?>
    <?= $contentThumbnail($item, ['sizes' => '(max-width: 1024px) 100vw, 1024px', 'loading' => 'eager']) ?>
    <?php $terms = (array)($item['terms'] ?? []); ?>
    <?php if ($terms !== []): ?>
        <ul class="term-list">
            <?php foreach ($terms as $term): ?>
                <li><a href="<?= $e($termUrl($term)) ?>"><?= $e((string)($term['name'] ?? '')) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <div class="content-body"><?= (string)($item['body'] ?? '') ?></div>
</article>
