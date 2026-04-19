<?php
$thumbnail = trim((string)($item['thumbnail'] ?? ''));
if ($thumbnail === '') {
    return;
}
$imageSizes = trim((string)($sizes ?? '(max-width: 1024px) 100vw, 1024px'));
$imageLoading = trim((string)($loading ?? 'lazy'));
$imageClass = trim((string)($class ?? 'content-cover'));
?>
<figure class="<?= $e($imageClass) ?>">
    <img
        src="<?= $e($mediaUrl($thumbnail, 'webp')) ?>"
        srcset="<?= $e($mediaSrcSet($thumbnail)) ?>"
        sizes="<?= $e($imageSizes) ?>"
        alt="<?= $e((string)($item['name'] ?? '')) ?>"
        loading="<?= $e($imageLoading) ?>"
        decoding="async"
    >
</figure>
