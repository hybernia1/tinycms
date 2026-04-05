<?php
declare(strict_types=1);

namespace App\View;

final class MetaHead
{
    public function render(array $meta): string
    {
        $title = $this->clean((string)($meta['title'] ?? 'TinyCMS'));
        $description = $this->clean((string)($meta['description'] ?? ''));
        $robots = $this->clean((string)($meta['robots'] ?? 'index,follow'));
        $keywords = $this->keywords($meta['keywords'] ?? '');
        $url = $this->clean((string)($meta['url'] ?? ''));
        $shortlink = $this->clean((string)($meta['shortlink'] ?? ''));
        $ogType = $this->clean((string)($meta['og_type'] ?? 'website'));
        $ogImage = $this->clean((string)($meta['og_image'] ?? ''));
        $siteName = $this->clean((string)($meta['site_name'] ?? ''));
        $author = $this->clean((string)($meta['author'] ?? ''));
        $themeColor = $this->clean((string)($meta['theme_color'] ?? ''));
        $jsonLd = $this->jsonLdScript($meta);

        $parts = [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>' . $this->esc($title) . '</title>',
            '<meta name="robots" content="' . $this->esc($robots) . '">',
            '<meta property="og:title" content="' . $this->esc($title) . '">',
            '<meta property="og:type" content="' . $this->esc($ogType) . '">',
            '<meta name="twitter:card" content="' . $this->esc($ogImage !== '' ? 'summary_large_image' : 'summary') . '">',
            '<meta name="twitter:title" content="' . $this->esc($title) . '">',
        ];

        if ($description !== '') {
            $parts[] = '<meta name="description" content="' . $this->esc($description) . '">';
            $parts[] = '<meta property="og:description" content="' . $this->esc($description) . '">';
            $parts[] = '<meta name="twitter:description" content="' . $this->esc($description) . '">';
        }

        if ($keywords !== '') {
            $parts[] = '<meta name="keywords" content="' . $this->esc($keywords) . '">';
        }

        if ($url !== '') {
            $parts[] = '<meta property="og:url" content="' . $this->esc($url) . '">';
            $parts[] = '<link rel="canonical" href="' . $this->esc($url) . '">';
        }

        if ($shortlink !== '') {
            $parts[] = '<link rel="shortlink" href="' . $this->esc($shortlink) . '">';
        }

        if ($ogImage !== '') {
            $parts[] = '<meta property="og:image" content="' . $this->esc($ogImage) . '">';
            $parts[] = '<meta name="twitter:image" content="' . $this->esc($ogImage) . '">';
        }

        if ($siteName !== '') {
            $parts[] = '<meta property="og:site_name" content="' . $this->esc($siteName) . '">';
        }

        if ($author !== '') {
            $parts[] = '<meta name="author" content="' . $this->esc($author) . '">';
        }

        if ($themeColor !== '') {
            $parts[] = '<meta name="theme-color" content="' . $this->esc($themeColor) . '">';
        }

        if ($jsonLd !== '') {
            $parts[] = $jsonLd;
        }

        return implode("\n", $parts);
    }

    private function jsonLdScript(array $meta): string
    {
        $structured = $meta['structured_data'] ?? null;
        if (is_string($structured) && trim($structured) !== '') {
            return '<script type="application/ld+json">' . trim($structured) . '</script>';
        }

        $payload = is_array($structured) ? $structured : $this->defaultStructuredData($meta);
        if ($payload === []) {
            return '';
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return '';
        }

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    private function defaultStructuredData(array $meta): array
    {
        $title = $this->clean((string)($meta['title'] ?? ''));
        $description = $this->clean((string)($meta['description'] ?? ''));
        $url = $this->clean((string)($meta['url'] ?? ''));
        $image = $this->clean((string)($meta['og_image'] ?? ''));
        $type = $this->clean((string)($meta['og_type'] ?? 'website'));

        if ($title === '') {
            return [];
        }

        $schemaType = $type === 'article' ? 'Article' : 'WebSite';
        $data = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $title,
        ];

        if ($description !== '') {
            $data['description'] = $description;
        }
        if ($url !== '') {
            $data['url'] = $url;
        }
        if ($image !== '') {
            $data['image'] = $image;
        }

        return $data;
    }

    private function keywords(mixed $value): string
    {
        if (is_string($value)) {
            return $this->clean($value);
        }

        if (!is_array($value)) {
            return '';
        }

        $items = [];
        foreach ($value as $item) {
            $keyword = $this->clean((string)$item);
            if ($keyword !== '') {
                $items[] = $keyword;
            }
        }

        return implode(', ', array_unique($items));
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
