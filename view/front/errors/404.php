<?php
$mode = (string)($notFoundMode ?? 'html');
if ($mode === 'image'): ?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" role="img" aria-label="404 Not Found">
    <rect width="640" height="360" fill="#f3f4f6"/>
    <path d="M0 300l140-120 90 75 120-105 110 90 70-55 110 115H0z" fill="#d1d5db"/>
    <circle cx="200" cy="120" r="36" fill="#9ca3af"/>
    <text x="50%" y="88%" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,Roboto,sans-serif" font-size="30" fill="#4b5563">404 Not Found</text>
</svg>
<?php elseif ($mode === 'xml'): ?>
<error>
    <code>404</code>
    <message>Not Found</message>
</error>
<?php elseif ($mode === 'text'): ?>
404 Not Found
<?php else: ?>
<main class="container py-4">
    <h1>404</h1>
    <p>Page not found.</p>
</main>
<?php endif; ?>
