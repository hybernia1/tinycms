<?php
$mode = (string)($notFoundMode ?? 'html');
if ($mode === 'image'): ?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" role="img" aria-label="<?= htmlspecialchars($t('front.not_found.title', '404 Not Found'), ENT_QUOTES, 'UTF-8') ?>">
    <rect width="640" height="360" fill="#f3f4f6"/>
    <path d="M0 300l140-120 90 75 120-105 110 90 70-55 110 115H0z" fill="#d1d5db"/>
    <circle cx="200" cy="120" r="36" fill="#9ca3af"/>
</svg>
<?php elseif ($mode === 'xml'): ?>
<error>
    <code>404</code>
    <message>
        <cs><?= htmlspecialchars($t('front.not_found.message_cs', 'Požadovaný dokument nebyl nalezen.'), ENT_XML1, 'UTF-8') ?></cs>
        <en><?= htmlspecialchars($t('front.not_found.message_en', 'Requested document was not found.'), ENT_XML1, 'UTF-8') ?></en>
    </message>
    <detail>
        <cs><?= htmlspecialchars($t('front.not_found.detail_cs', 'Zkontrolujte URL adresu nebo požadovaný XML dokument.'), ENT_XML1, 'UTF-8') ?></cs>
        <en><?= htmlspecialchars($t('front.not_found.detail_en', 'Check the URL or requested XML document.'), ENT_XML1, 'UTF-8') ?></en>
    </detail>
</error>
<?php elseif ($mode === 'text'): ?>
<?= htmlspecialchars($t('front.not_found.title', '404 Not Found'), ENT_QUOTES, 'UTF-8') ?>
<?php else: ?>
<main class="container py-4">
    <h1>404</h1>
    <p><?= htmlspecialchars($t('front.not_found.page', 'Page not found.'), ENT_QUOTES, 'UTF-8') ?></p>
</main>
<?php endif; ?>
