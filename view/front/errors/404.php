<?php
$mode = (string)($notFoundMode ?? 'html');
if ($mode === 'image'): ?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" role="img" aria-label="<?= htmlspecialchars($t('front.not_found.title'), ENT_QUOTES, 'UTF-8') ?>">
    <rect width="640" height="360" fill="#f3f4f6"/>
    <path d="M0 300l140-120 90 75 120-105 110 90 70-55 110 115H0z" fill="#d1d5db"/>
    <circle cx="200" cy="120" r="36" fill="#9ca3af"/>
</svg>
<?php elseif ($mode === 'document'): ?>
404
<?= htmlspecialchars($t('front.not_found.message'), ENT_QUOTES, 'UTF-8') ?>

<?= htmlspecialchars($t('front.not_found.detail'), ENT_QUOTES, 'UTF-8') ?>
<?php elseif ($mode === 'text'): ?>
<?= htmlspecialchars($t('front.not_found.title'), ENT_QUOTES, 'UTF-8') ?>
<?php else: ?>
<main class="container py-4">
    <h1>404</h1>
    <p><?= htmlspecialchars($t('front.not_found.page'), ENT_QUOTES, 'UTF-8') ?></p>
</main>
<?php endif; ?>
