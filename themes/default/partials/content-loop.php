<section class="content-loop">
    <?php foreach ($items as $loopItem): ?>
        <article class="content-card">
            <?php if (trim((string)($loopItem['thumbnail'] ?? '')) !== ''): ?>
                <a href="<?= $e($contentUrl($loopItem)) ?>" class="content-card-thumb">
                    <img
                        src="<?= $e($mediaUrl((string)$loopItem['thumbnail'], 'small')) ?>"
                        srcset="<?= $e($mediaSrcSet((string)$loopItem['thumbnail'])) ?>"
                        sizes="(max-width: 768px) 100vw, 300px"
                        alt="<?= $e((string)($loopItem['name'] ?? '')) ?>"
                        loading="lazy"
                        decoding="async"
                    >
                </a>
            <?php endif; ?>
            <h2>
                <a href="<?= $e($contentUrl($loopItem)) ?>"><?= $e((string)($loopItem['name'] ?? '')) ?></a>
            </h2>
            <p><?= $e((string)($loopItem['excerpt'] ?? '')) ?></p>
        </article>
    <?php endforeach; ?>
</section>
