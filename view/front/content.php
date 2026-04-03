<div class="container py-5">
    <div class="row">
        <div class="col-12 col-lg-8">
            <article class="card p-4">
                <h1 class="h2 mb-3"><?= $e((string)($item['name'] ?? '')) ?></h1>
                <p class="text-muted mb-3"><?= $e($d((string)($item['created'] ?? '')) . ' ' . $t((string)($item['created'] ?? ''))) ?></p>
                <?php if ((string)($item['excerpt'] ?? '') !== ''): ?>
                <p class="mb-4"><?= nl2br($e((string)($item['excerpt'] ?? ''))) ?></p>
                <?php endif; ?>
                <div><?= nl2br($e((string)($item['body'] ?? ''))) ?></div>
            </article>
        </div>
    </div>
</div>
