<section class="content-loop">
    <?php foreach ($items as $loopItem): ?>
        <article class="content-card">
            <h2>
                <a href="<?= $e($url('content/' . (int)($loopItem['id'] ?? 0))) ?>"><?= $e((string)($loopItem['name'] ?? '')) ?></a>
            </h2>
            <p><?= $e((string)($loopItem['excerpt'] ?? '')) ?></p>
        </article>
    <?php endforeach; ?>
</section>
