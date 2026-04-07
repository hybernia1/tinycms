<?php
$channelTitle = (string)($channel['title'] ?? 'TinyCMS');
$channelLink = (string)($channel['link'] ?? '/');
$channelDescription = (string)($channel['description'] ?? '');
$selfLink = (string)($channel['self'] ?? $channelLink);
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= htmlspecialchars($channelTitle, ENT_XML1, 'UTF-8') ?></title>
        <link><?= htmlspecialchars($channelLink, ENT_XML1, 'UTF-8') ?></link>
        <description><?= htmlspecialchars($channelDescription, ENT_XML1, 'UTF-8') ?></description>
        <atom:link href="<?= htmlspecialchars($selfLink, ENT_XML1, 'UTF-8') ?>" rel="self" type="application/rss+xml" />
        <?php foreach ($items as $item): ?>
            <item>
                <title><?= htmlspecialchars((string)($item['title'] ?? ''), ENT_XML1, 'UTF-8') ?></title>
                <link><?= htmlspecialchars((string)($item['link'] ?? ''), ENT_XML1, 'UTF-8') ?></link>
                <guid isPermaLink="true"><?= htmlspecialchars((string)($item['guid'] ?? ''), ENT_XML1, 'UTF-8') ?></guid>
                <pubDate><?= htmlspecialchars((string)($item['pubDate'] ?? ''), ENT_XML1, 'UTF-8') ?></pubDate>
                <description><?= htmlspecialchars((string)($item['description'] ?? ''), ENT_XML1, 'UTF-8') ?></description>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>
