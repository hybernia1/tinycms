<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Media;
use App\Service\Support\Slugger;

final class Theme
{
    private string $theme;
    private Slugger $slugger;

    public function __construct(private Router $router, private array $settings, string $theme)
    {
        $this->theme = trim($theme) !== '' ? trim($theme) : 'default';
        $this->slugger = new Slugger();
    }

    public function setting(string $key, string $default = ''): string
    {
        return (string)($this->settings[$key] ?? $default);
    }

    public function siteTitle(): string
    {
        return $this->setting('sitename', 'TinyCMS');
    }

    public function siteLogo(): string
    {
        return $this->setting('logo');
    }

    public function pageTitle(?string $value = null): string
    {
        if ($value !== null && trim($value) !== '') {
            return trim($value);
        }

        $meta = trim($this->setting('meta_title'));
        return $meta !== '' ? $meta : $this->siteTitle();
    }

    public function head(array $context = []): string
    {
        $kind = trim((string)($context['kind'] ?? 'home'));
        $item = is_array($context['item'] ?? null) ? $context['item'] : [];
        $term = is_array($context['term'] ?? null) ? $context['term'] : [];
        $title = $this->resolveHeadTitle($kind, $item, $term, isset($context['pageTitle']) ? (string)$context['pageTitle'] : null);
        $description = $this->resolveHeadDescription($kind, $item, $term);
        $ogType = $this->resolveOgType($kind, $item);
        $tags = [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>' . $this->esc($title) . '</title>',
            '<meta name="description" content="' . $this->esc($description) . '">',
            '<meta property="og:title" content="' . $this->esc($title) . '">',
            '<meta property="og:description" content="' . $this->esc($description) . '">',
            '<meta property="og:type" content="' . $this->esc($ogType) . '">',
        ];

        $contentType = trim((string)($item['type'] ?? ''));
        if ($contentType !== '') {
            $tags[] = '<meta name="content:type" content="' . $this->esc($contentType) . '">';
        }

        return implode(PHP_EOL, $tags);
    }

    public function url(string $path = ''): string
    {
        return $this->router->url($path);
    }

    public function themeUrl(string $path = ''): string
    {
        $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/');
        return $this->url(trim($themeDir . '/' . $this->theme . '/' . ltrim($path, '/'), '/'));
    }

    public function mediaUrl(string $path = '', string $size = 'origin'): string
    {
        return $this->url(Media::bySize($path, $size));
    }

    public function contentUrl(array $item): string
    {
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0) {
            return $this->url('');
        }

        return $this->url($this->slugger->slug((string)($item['name'] ?? ''), $id));
    }

    public function termUrl(array $term): string
    {
        $id = (int)($term['id'] ?? 0);
        if ($id <= 0) {
            return $this->url('term');
        }

        return $this->url('term/' . $this->slugger->slug((string)($term['name'] ?? ''), $id));
    }

    public function mediaSrcSet(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        $sources = [];
        foreach (Media::variants() as $variant) {
            $name = trim((string)($variant['name'] ?? ''));
            $width = (int)($variant['width'] ?? 0);
            if ($name === '' || $width <= 0) {
                continue;
            }

            $sources[] = $this->mediaUrl($trimmed, $name) . ' ' . $width . 'w';
        }

        $sources[] = $this->mediaUrl($trimmed, 'webp') . ' 1024w';
        return implode(', ', $sources);
    }

    public function contentThumbnail(array $item, array $options = []): string
    {
        $thumbnail = trim((string)($item['thumbnail'] ?? ''));
        if ($thumbnail === '') {
            return '';
        }

        $size = trim((string)($options['size'] ?? 'webp'));
        $sizes = trim((string)($options['sizes'] ?? '(max-width: 1024px) 100vw, 1024px'));
        $loading = trim((string)($options['loading'] ?? 'lazy'));
        $class = trim((string)($options['class'] ?? 'content-cover'));
        $name = trim((string)($item['name'] ?? ''));

        return sprintf(
            '<figure class="%s"><img src="%s" srcset="%s" sizes="%s" alt="%s" loading="%s" decoding="async"></figure>',
            $this->esc($class),
            $this->esc($this->mediaUrl($thumbnail, $size)),
            $this->esc($this->mediaSrcSet($thumbnail)),
            $this->esc($sizes),
            $this->esc($name),
            $this->esc($loading),
        );
    }

    public function contentAuthor(array $item, string $fallback = ''): string
    {
        $author = trim((string)($item['author_name'] ?? ''));
        if ($author !== '') {
            return $author;
        }

        return trim($fallback);
    }

    public function contentDate(array $item, string $fallback = ''): string
    {
        $raw = trim((string)($item['created'] ?? ''));
        if ($raw === '') {
            return trim($fallback);
        }

        $timestamp = $this->timestamp($raw);
        if ($timestamp === null) {
            return trim($fallback);
        }

        $format = (string)(defined('APP_DATETIME_FORMAT') ? APP_DATETIME_FORMAT : 'Y-m-d H:i:s');
        return date($format, $timestamp);
    }

    public function pagination(array $pagination, string $basePath = '', array $labels = []): string
    {
        $totalPages = (int)($pagination['total_pages'] ?? 1);
        $page = (int)($pagination['page'] ?? 1);
        if ($totalPages <= 1) {
            return '';
        }

        $current = max(1, min($page, $totalPages));
        $prevLabel = trim((string)($labels['prev'] ?? 'Previous'));
        $nextLabel = trim((string)($labels['next'] ?? 'Next'));
        $items = [];

        if ($current > 1) {
            $items[] = sprintf(
                '<a href="%s">%s</a>',
                $this->esc($this->paginationUrl($basePath, $current - 1)),
                $this->esc($prevLabel !== '' ? $prevLabel : 'Previous'),
            );
        }

        $items[] = sprintf('<span>%d / %d</span>', $current, $totalPages);

        if ($current < $totalPages) {
            $items[] = sprintf(
                '<a href="%s">%s</a>',
                $this->esc($this->paginationUrl($basePath, $current + 1)),
                $this->esc($nextLabel !== '' ? $nextLabel : 'Next'),
            );
        }

        return '<nav class="pagination" aria-label="Pagination">' . implode('', $items) . '</nav>';
    }

    private function paginationUrl(string $basePath, int $page): string
    {
        $base = trim($basePath, '/');
        $suffix = $page > 1 ? '?page=' . $page : '';
        return $this->url($base . $suffix);
    }

    private function resolveHeadTitle(string $kind, array $item, array $term, ?string $customTitle): string
    {
        $custom = trim((string)$customTitle);
        if ($custom !== '') {
            return $custom;
        }

        if (($kind === 'content' || $kind === 'home-content') && trim((string)($item['name'] ?? '')) !== '') {
            return trim((string)$item['name']) . ' | ' . $this->siteTitle();
        }

        if ($kind === 'archive' && trim((string)($term['name'] ?? '')) !== '') {
            return trim((string)$term['name']) . ' | ' . $this->siteTitle();
        }

        return $this->pageTitle(null);
    }

    private function resolveHeadDescription(string $kind, array $item, array $term): string
    {
        if ($kind === 'content' || $kind === 'home-content') {
            $excerpt = $this->plainText((string)($item['excerpt'] ?? ''));
            if ($excerpt !== '') {
                return $excerpt;
            }

            $body = $this->plainText((string)($item['body'] ?? ''));
            if ($body !== '') {
                return $body;
            }
        }

        if ($kind === 'archive' && trim((string)($term['name'] ?? '')) !== '') {
            return trim((string)$term['name']);
        }

        $meta = $this->plainText($this->setting('meta_description'));
        return $meta !== '' ? $meta : $this->siteTitle();
    }

    private function resolveOgType(string $kind, array $item): string
    {
        if ($kind !== 'content' && $kind !== 'home-content') {
            return 'website';
        }

        $type = trim((string)($item['type'] ?? ''));
        return in_array($type, ['article', 'news_article', 'blog_posting'], true) ? 'article' : 'website';
    }

    private function plainText(string $value, int $limit = 160): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $clean = preg_replace('/\s+/', ' ', $clean) ?? '';
        return $limit > 0 ? mb_substr($clean, 0, $limit) : $clean;
    }

    private function timestamp(string $value): ?int
    {
        $clean = trim($value);
        if ($clean === '') {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $clean);
            if ($date instanceof \DateTimeImmutable && $date->format($format) === $clean) {
                return $date->getTimestamp();
            }
        }

        $timestamp = strtotime($clean);
        return $timestamp === false ? null : $timestamp;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
