<section class="content-loop">
    <?php foreach ($items as $loopItem): ?>
        <article class="content-card">
            <?php $thumbnail = trim((string)($loopItem['thumbnail'] ?? '')); ?>
            <a href="<?= $e($contentUrl($loopItem)) ?>" class="content-card-thumb">
                <?php if ($thumbnail !== ''): ?>
                    <img
                        src="<?= $e($mediaUrl($thumbnail, 'small')) ?>"
                        srcset="<?= $e($mediaSrcSet($thumbnail)) ?>"
                        sizes="120px"
                        alt="<?= $e((string)($loopItem['name'] ?? '')) ?>"
                        loading="lazy"
                        decoding="async"
                    >
                <?php endif; ?>
            </a>
            <div class="content-card-body">
                <h2>
                    <a href="<?= $e($contentUrl($loopItem)) ?>"><?= $e((string)($loopItem['name'] ?? '')) ?></a>
                </h2>
                <?php $meta = array_values(array_filter([$contentDate($loopItem), $contentAuthor($loopItem)])); ?>
                <?php if ($meta !== []): ?>
                    <p class="text-muted small"><?= $e(implode(' · ', $meta)) ?></p>
                <?php endif; ?>
                <p><?= $e((string)($loopItem['excerpt'] ?? '')) ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</section>
