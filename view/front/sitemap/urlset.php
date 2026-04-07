<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($urls as $url): ?>
        <url>
            <loc><?= htmlspecialchars((string)($url['loc'] ?? ''), ENT_XML1, 'UTF-8') ?></loc>
            <?php if ((string)($url['lastmod'] ?? '') !== ''): ?>
                <lastmod><?= htmlspecialchars((string)$url['lastmod'], ENT_XML1, 'UTF-8') ?></lastmod>
            <?php endif; ?>
        </url>
    <?php endforeach; ?>
</urlset>
