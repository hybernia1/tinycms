<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Support\Slugger;

final class WordpressImporter
{
    private const PER_PAGE = 20;

    private array $mediaByUrl = [];

    public function __construct(
        private Content $content,
        private Media $media,
        private Term $terms,
        private Slugger $slugger,
        private string $rootPath,
    ) {
    }

    public function import(string $siteUrl, int $authorId, int $startPage = 1, int $batchPages = 2): array
    {
        $baseUrl = $this->normalizeSiteUrl($siteUrl);
        if ($baseUrl === '') {
            return ['success' => false, 'message' => 'Neplatná URL WordPress webu.'];
        }

        $page = max(1, $startPage);
        $batch = max(1, min(10, $batchPages));
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $totalPages = null;

        for ($step = 0; $step < $batch; $step++) {
            $response = $this->fetchPosts($baseUrl, $page);
            if (($response['ok'] ?? false) !== true) {
                return [
                    'success' => false,
                    'message' => (string)($response['message'] ?? 'Import selhal.'),
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'failed' => $failed,
                    'next_page' => $page,
                ];
            }

            $totalPages = (int)($response['total_pages'] ?? 1);
            $posts = (array)($response['posts'] ?? []);
            if ($posts === []) {
                break;
            }

            foreach ($posts as $post) {
                $result = $this->importPost((array)$post, $authorId);
                if ($result === 'imported') {
                    $imported++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                } else {
                    $failed++;
                }
            }

            if ($totalPages > 0 && $page >= $totalPages) {
                $page++;
                break;
            }

            $page++;
        }

        $resolvedTotalPages = max(1, (int)($totalPages ?? 1));
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'next_page' => $page,
            'has_more' => $page <= $resolvedTotalPages,
            'total_pages' => $resolvedTotalPages,
        ];
    }

    private function importPost(array $post, int $authorId): string
    {
        $source = trim((string)($post['link'] ?? ''));
        if ($source === '' || $this->content->findBySource($source) !== null) {
            return 'skipped';
        }

        $name = $this->cleanText((string)($post['title']['rendered'] ?? ''));
        if ($name === '') {
            $name = 'WP import';
        }

        $body = (string)($post['content']['rendered'] ?? '');
        [$body, $bodyMediaIds] = $this->importContentImages($body, $authorId);

        $thumbnailId = $this->importFeaturedMedia($post, $authorId);
        if ($thumbnailId > 0) {
            $bodyMediaIds[] = $thumbnailId;
        }

        $status = (string)($post['status'] ?? '') === 'publish'
            ? Content::STATUS_PUBLISHED
            : Content::STATUS_DRAFT;

        $save = $this->content->save([
            'name' => $name,
            'status' => $status,
            'type' => Content::TYPE_ARTICLE,
            'excerpt' => $this->cleanText((string)($post['excerpt']['rendered'] ?? '')),
            'body' => $body,
            'author' => (string)$authorId,
            'created' => (string)($post['date'] ?? ''),
            'source' => $source,
        ], $authorId);

        if (($save['success'] ?? false) !== true) {
            return 'failed';
        }

        $contentId = (int)($save['id'] ?? 0);
        if ($contentId <= 0) {
            return 'failed';
        }

        if ($thumbnailId > 0) {
            $this->content->setThumbnail($contentId, $thumbnailId);
        }

        $tagNames = $this->extractTagNames($post);
        if ($tagNames !== []) {
            $this->terms->syncContentTerms($contentId, implode(',', $tagNames));
        }

        $bodyMediaIds = array_values(array_unique(array_filter($bodyMediaIds, static fn(int $id): bool => $id > 0)));
        foreach ($bodyMediaIds as $mediaId) {
            $this->content->attachMedia($contentId, $mediaId);
        }

        return 'imported';
    }

    private function extractTagNames(array $post): array
    {
        $embeddedTerms = (array)($post['_embedded']['wp:term'] ?? []);
        $names = [];

        foreach ($embeddedTerms as $group) {
            foreach ((array)$group as $term) {
                if ((string)($term['taxonomy'] ?? '') !== 'post_tag') {
                    continue;
                }

                $name = trim((string)($term['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $key = mb_strtolower($name);
                $names[$key] = mb_substr($name, 0, 255);
            }
        }

        return array_values($names);
    }

    private function importFeaturedMedia(array $post, int $authorId): int
    {
        $items = (array)($post['_embedded']['wp:featuredmedia'] ?? []);
        $url = trim((string)($items[0]['source_url'] ?? ''));
        if ($url === '') {
            return 0;
        }

        return $this->importImage($url, $authorId);
    }

    private function importContentImages(string $html, int $authorId): array
    {
        if (trim($html) === '') {
            return [$html, []];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?><div id="wp-import-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if ($loaded !== true) {
            return [$html, []];
        }

        $ids = [];
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            if (!$image instanceof \DOMElement) {
                continue;
            }

            $src = trim($image->getAttribute('src'));
            if ($src === '') {
                continue;
            }

            $mediaId = $this->importImage($src, $authorId);
            if ($mediaId <= 0) {
                continue;
            }

            $media = $this->media->find($mediaId);
            $path = trim((string)($media['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $image->setAttribute('src', '/' . ltrim($path, '/'));
            $image->setAttribute('data-media-id', (string)$mediaId);
            $ids[] = $mediaId;
        }

        $root = $dom->getElementById('wp-import-root');
        if (!$root instanceof \DOMElement) {
            return [$html, $ids];
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= (string)$dom->saveHTML($child);
        }

        return [$output, $ids];
    }

    private function importImage(string $url, int $authorId): int
    {
        $normalized = $this->normalizeImageUrl($url);
        if ($normalized === '') {
            return 0;
        }

        if (isset($this->mediaByUrl[$normalized])) {
            return $this->mediaByUrl[$normalized];
        }

        $bytes = $this->fetchBinary($normalized);
        if ($bytes === '') {
            return 0;
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return 0;
        }

        $subdir = 'uploads/' . date('Y') . '/' . date('m');
        $absoluteDir = $this->rootPath . '/' . $subdir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            imagedestroy($image);
            return 0;
        }

        $baseName = pathinfo(parse_url($normalized, PHP_URL_PATH) ?? '', PATHINFO_FILENAME);
        $slug = $this->slugger->slug($baseName !== '' ? $baseName : 'import', random_int(1000, 999999));
        $relativePath = $subdir . '/' . $slug . '.webp';
        $absolutePath = $this->rootPath . '/' . $relativePath;

        $saved = @imagewebp($image, $absolutePath, 82);
        if ($saved !== true) {
            imagedestroy($image);
            return 0;
        }

        $this->createVariants($image, $absolutePath);
        imagedestroy($image);

        $mediaId = $this->media->create($authorId > 0 ? $authorId : null, $baseName !== '' ? $baseName : $slug, $relativePath);
        if ($mediaId <= 0) {
            @unlink($absolutePath);
            return 0;
        }

        $this->mediaByUrl[$normalized] = $mediaId;
        return $mediaId;
    }

    private function createVariants(\GdImage $source, string $absoluteWebpPath): void
    {
        $smallPath = (string)preg_replace('/\.webp$/i', '_small.webp', $absoluteWebpPath);
        $mediumPath = (string)preg_replace('/\.webp$/i', '_medium.webp', $absoluteWebpPath);

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width <= 0 || $height <= 0) {
            return;
        }

        $small = imagecreatetruecolor(300, 300);
        if ($small instanceof \GdImage) {
            imagealphablending($small, true);
            imagesavealpha($small, true);
            $scale = max(300 / $width, 300 / $height);
            $newWidth = (int)round($width * $scale);
            $newHeight = (int)round($height * $scale);
            $dstX = (int)floor((300 - $newWidth) / 2);
            $dstY = (int)floor((300 - $newHeight) / 2);
            imagecopyresampled($small, $source, $dstX, $dstY, 0, 0, $newWidth, $newHeight, $width, $height);
            @imagewebp($small, $smallPath, 82);
            imagedestroy($small);
        }

        $targetWidth = min(768, $width);
        $targetHeight = (int)round($height * ($targetWidth / $width));
        $medium = imagecreatetruecolor($targetWidth, max(1, $targetHeight));
        if ($medium instanceof \GdImage) {
            imagealphablending($medium, true);
            imagesavealpha($medium, true);
            imagecopyresampled($medium, $source, 0, 0, 0, 0, $targetWidth, max(1, $targetHeight), $width, $height);
            @imagewebp($medium, $mediumPath, 82);
            imagedestroy($medium);
        }
    }

    private function fetchPosts(string $baseUrl, int $page): array
    {
        $url = $baseUrl . '/wp-json/wp/v2/posts?per_page=' . self::PER_PAGE . '&page=' . max(1, $page) . '&_embed=wp:featuredmedia,wp:term';
        $response = $this->fetchJsonWithHeaders($url);

        if (($response['ok'] ?? false) !== true) {
            return ['ok' => false, 'message' => 'Nepodařilo se načíst wp-json příspěvky.'];
        }

        $posts = $response['json'];
        if (!is_array($posts)) {
            return ['ok' => false, 'message' => 'WordPress odpověď má neplatný formát.'];
        }

        $headers = (array)($response['headers'] ?? []);
        $totalPages = (int)($headers['x-wp-totalpages'] ?? 1);
        return ['ok' => true, 'posts' => $posts, 'total_pages' => max(1, $totalPages)];
    }

    private function fetchJsonWithHeaders(string $url): array
    {
        if (!function_exists('curl_init')) {
            $json = $this->fetchJsonFallback($url);
            return ['ok' => $json !== null, 'json' => $json, 'headers' => []];
        }

        $headers = [];
        $curl = curl_init($url);
        if ($curl === false) {
            return ['ok' => false, 'json' => null, 'headers' => []];
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => $this->userAgent(),
            CURLOPT_HEADERFUNCTION => static function ($curlHandle, string $headerLine) use (&$headers): int {
                $trimmed = trim($headerLine);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($headerLine);
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $headers[strtolower(trim($name))] = trim($value);
                return strlen($headerLine);
            },
        ]);

        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!is_string($raw) || $raw === '' || $status >= 400) {
            return ['ok' => false, 'json' => null, 'headers' => []];
        }

        $decoded = json_decode($raw, true);
        return ['ok' => is_array($decoded), 'json' => $decoded, 'headers' => $headers];
    }

    private function fetchJsonFallback(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => "User-Agent: {$this->userAgent()}\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function fetchBinary(string $url): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl !== false) {
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_USERAGENT => $this->userAgent(),
                ]);
                $raw = curl_exec($curl);
                $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if (is_string($raw) && $raw !== '' && $status < 400) {
                    return $raw;
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => "User-Agent: {$this->userAgent()}\r\n",
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        return is_string($raw) ? $raw : '';
    }

    private function normalizeSiteUrl(string $siteUrl): string
    {
        $normalized = trim($siteUrl);
        if ($normalized === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $normalized)) {
            $normalized = 'https://' . ltrim($normalized, '/');
        }

        if (filter_var($normalized, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return rtrim($normalized, '/');
    }

    private function normalizeImageUrl(string $url): string
    {
        $normalized = trim($url);
        if ($normalized === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $normalized)) {
            return '';
        }

        return filter_var($normalized, FILTER_VALIDATE_URL) !== false ? $normalized : '';
    }

    private function cleanText(string $value): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $clean) ?? '';
    }

    private function userAgent(): string
    {
        $version = defined('APP_VERSION') ? (string)APP_VERSION : '0.9.0';
        return 'TinyCMS/' . $version;
    }
}
