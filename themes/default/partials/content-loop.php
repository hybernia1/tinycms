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
                <?php $date = $contentDate($loopItem); ?>
                <?php $author = $contentAuthor($loopItem); ?>
                <?php if ($date !== '' || $author !== ''): ?>
                    <p class="text-muted small content-card-meta">
                        <?php if ($date !== ''): ?>
                            <span class="content-card-meta-item"><?= $icon('calendar') ?><span><?= $e($date) ?></span></span>
                        <?php endif; ?>
                        <?php if ($author !== ''): ?>
                            <span class="content-card-meta-item"><?= $icon('users') ?><span><?= $e($author) ?></span></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <p><?= $e((string)($loopItem['excerpt'] ?? '')) ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</section>
