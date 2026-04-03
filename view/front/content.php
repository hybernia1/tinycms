<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-8">
            <article class="card p-4">
                <h1 class="mb-3"><?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted mb-3"><?= htmlspecialchars((string)($item['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ((string)($item['excerpt'] ?? '') !== ''): ?>
                <p class="mb-4"><?= nl2br(htmlspecialchars((string)($item['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
                <div><?= nl2br(htmlspecialchars((string)($item['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
            </article>
        </div>
    </div>
</div>
