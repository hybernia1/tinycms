<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($paths as $path): ?>
        <sitemap>
            <loc><?= htmlspecialchars($absoluteUrl((string)$path), ENT_XML1, 'UTF-8') ?></loc>
        </sitemap>
    <?php endforeach; ?>
</sitemapindex>
