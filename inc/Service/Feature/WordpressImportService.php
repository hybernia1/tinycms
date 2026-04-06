<?php
declare(strict_types=1);

namespace App\Service\Feature;

final class WordpressImportService
{
    public function __construct(
        private ContentService $content,
        private MediaService $media,
        private UploadService $upload,
        private TermService $terms,
    ) {
    }

    public function import(string $siteUrl, ?int $limit, bool $importAll, int $authorId): array
    {
        $normalizedSiteUrl = rtrim(trim($siteUrl), '/');
        if ($normalizedSiteUrl === '' || !preg_match('#^https?://#i', $normalizedSiteUrl)) {
            return ['success' => false, 'error' => 'INVALID_SITE'];
        }

        $posts = $this->fetchPosts($normalizedSiteUrl, $limit, $importAll);
        if (($posts['success'] ?? false) !== true) {
            return $posts;
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ((array)($posts['items'] ?? []) as $post) {
            $wpId = (int)($post['id'] ?? 0);
            if ($wpId <= 0 || $this->content->existsByWpImportId($wpId)) {
                $skipped++;
                continue;
            }

            $mediaCache = [];
            $name = $this->resolveName($post);
            $created = $this->resolveCreatedAt($post);
            $body = $this->sanitizeAndImportBody((string)($post['content']['rendered'] ?? ''), $authorId, $mediaCache);
            $body .= '<div data-wp-import-id="' . $wpId . '"></div>';
            $excerpt = trim(strip_tags((string)($post['excerpt']['rendered'] ?? '')));

            $saved = $this->content->save([
                'name' => $name,
                'status' => ((string)($post['status'] ?? 'publish')) === 'publish' ? 'published' : 'draft',
                'excerpt' => $excerpt,
                'body' => $body,
                'author' => $authorId > 0 ? (string)$authorId : '',
                'created' => $created,
            ], $authorId);

            if (($saved['success'] ?? false) !== true) {
                $failed++;
                continue;
            }

            $contentId = (int)($saved['id'] ?? 0);
            if ($contentId <= 0) {
                $failed++;
                continue;
            }

            $termNames = $this->extractTermNames($post);
            if ($termNames !== []) {
                $this->terms->syncContentTerms($contentId, implode(', ', $termNames));
            }

            $thumbnailUrl = $this->extractThumbnailUrl($post);
            if ($thumbnailUrl !== '') {
                $thumbnailId = $this->importMediaByUrl($thumbnailUrl, $authorId, $mediaCache);
                if ($thumbnailId > 0) {
                    $this->content->setThumbnail($contentId, $thumbnailId);
                    $this->content->attachMedia($contentId, $thumbnailId);
                }
            }

            $imported++;
        }

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    private function fetchPosts(string $siteUrl, ?int $limit, bool $importAll): array
    {
        $perPage = 100;
        $items = [];
        $target = $importAll ? PHP_INT_MAX : max(1, (int)($limit ?? 10));
        $page = 1;

        while (count($items) < $target) {
            $url = $siteUrl . '/wp-json/wp/v2/posts?' . http_build_query([
                'per_page' => min($perPage, $target - count($items)),
                'page' => $page,
                'orderby' => 'date',
                'order' => 'desc',
                '_embed' => 'wp:featuredmedia,wp:term',
            ]);
            $response = $this->requestJson($url);
            if (($response['success'] ?? false) !== true) {
                return ['success' => false, 'error' => 'FETCH_FAILED'];
            }

            $batch = (array)($response['data'] ?? []);
            if ($batch === []) {
                break;
            }

            foreach ($batch as $row) {
                $items[] = $row;
                if (count($items) >= $target) {
                    break;
                }
            }

            if (count($batch) < $perPage) {
                break;
            }

            $page++;
        }

        return ['success' => true, 'items' => $items];
    }

    private function requestJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'follow_location' => 1,
                'max_redirects' => 5,
                'header' => "User-Agent: TinyCMS-Importer/1.0\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return ['success' => false];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return ['success' => false];
        }

        return ['success' => true, 'data' => $decoded];
    }

    private function resolveName(array $post): string
    {
        $title = html_entity_decode((string)($post['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = trim(strip_tags($title));
        return $title !== '' ? $title : 'WP post';
    }

    private function resolveCreatedAt(array $post): string
    {
        $raw = (string)($post['date'] ?? '');
        $stamp = $raw !== '' ? strtotime($raw) : false;
        if ($stamp === false) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $stamp);
    }

    private function sanitizeAndImportBody(string $body, int $authorId, array &$mediaCache): string
    {
        $cleaned = preg_replace('/\[[^\]]+\]/', '', $body) ?? $body;
        $wrapped = '<div>' . $cleaned . '</div>';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->documentElement;
        if (!$root instanceof \DOMElement) {
            return '';
        }

        $this->sanitizeNode($root, $authorId, $mediaCache);

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return trim($html);
    }

    private function sanitizeNode(\DOMNode $node, int $authorId, array &$mediaCache): void
    {
        if (!$node->hasChildNodes()) {
            return;
        }

        $allowed = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'h1', 'h2', 'h3', 'h4', 'blockquote'];
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, $allowed, true)) {
                    $this->unwrapNode($child);
                    continue;
                }

                $this->sanitizeAttributes($child, $authorId, $mediaCache);
                $this->sanitizeNode($child, $authorId, $mediaCache);
                continue;
            }

            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
            }
        }
    }

    private function sanitizeAttributes(\DOMElement $element, int $authorId, array &$mediaCache): void
    {
        $allowedMap = [
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'data-media-id'],
        ];

        $tag = strtolower($element->tagName);
        $allowedAttrs = $allowedMap[$tag] ?? [];
        $attrs = [];
        foreach ($element->attributes as $attr) {
            $attrs[] = $attr->name;
        }

        foreach ($attrs as $name) {
            if (!in_array(strtolower($name), $allowedAttrs, true)) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a') {
            $href = trim((string)$element->getAttribute('href'));
            if ($href === '' || (!str_starts_with($href, 'http://') && !str_starts_with($href, 'https://') && !str_starts_with($href, '/'))) {
                $element->removeAttribute('href');
            }
        }

        if ($tag === 'img') {
            $src = trim((string)$element->getAttribute('src'));
            if ($src === '' || !preg_match('#^https?://#i', $src)) {
                $element->parentNode?->removeChild($element);
                return;
            }

            $mediaId = $this->importMediaByUrl($src, $authorId, $mediaCache);
            if ($mediaId <= 0) {
                return;
            }

            $media = $this->media->find($mediaId);
            if ($media === null) {
                return;
            }

            $path = trim((string)($media['path_webp'] ?? ''));
            if ($path === '') {
                $path = trim((string)($media['path'] ?? ''));
            }
            if ($path === '') {
                return;
            }

            $element->setAttribute('src', '/' . ltrim($path, '/'));
            $element->setAttribute('data-media-id', (string)$mediaId);
        }
    }

    private function unwrapNode(\DOMElement $node): void
    {
        $parent = $node->parentNode;
        if ($parent === null) {
            return;
        }

        while ($node->firstChild !== null) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private function importMediaByUrl(string $url, int $authorId, array &$mediaCache): int
    {
        $key = trim($url);
        if ($key === '') {
            return 0;
        }

        if (isset($mediaCache[$key])) {
            return (int)$mediaCache[$key];
        }

        $upload = $this->upload->importRemoteImage($key);
        if (($upload['success'] ?? false) !== true) {
            return 0;
        }

        $data = (array)($upload['data'] ?? []);
        $mediaId = $this->media->create(
            $authorId > 0 ? $authorId : null,
            (string)($data['name'] ?? ''),
            (string)($data['path'] ?? ''),
            (string)($data['path_webp'] ?? '')
        );

        if ($mediaId <= 0) {
            $this->upload->deleteMediaFiles($data);
            return 0;
        }

        $mediaCache[$key] = $mediaId;
        return $mediaId;
    }

    private function extractTermNames(array $post): array
    {
        $terms = [];
        foreach ((array)($post['_embedded']['wp:term'] ?? []) as $group) {
            foreach ((array)$group as $term) {
                $name = trim((string)($term['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $terms[mb_strtolower($name)] = mb_substr($name, 0, 255);
            }
        }

        return array_values($terms);
    }

    private function extractThumbnailUrl(array $post): string
    {
        $media = (array)($post['_embedded']['wp:featuredmedia'][0] ?? []);
        return trim((string)($media['source_url'] ?? ''));
    }
}
