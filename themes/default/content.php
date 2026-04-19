<article class="content-single">
    <h1><?= $e((string)($item['name'] ?? '')) ?></h1>
    <?php $terms = (array)($item['terms'] ?? []); ?>
    <?php if ($terms !== []): ?>
        <ul class="term-list">
            <?php foreach ($terms as $term): ?>
                <li><a href="<?= $e($url('term/' . (int)($term['id'] ?? 0))) ?>"><?= $e((string)($term['name'] ?? '')) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <div class="content-body"><?= (string)($item['body'] ?? '') ?></div>
</article>
