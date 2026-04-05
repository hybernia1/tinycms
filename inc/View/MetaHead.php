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

        $parts = [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>' . $this->esc($title) . '</title>',
            '<meta name="robots" content="' . $this->esc($robots) . '">',
            '<meta property="og:title" content="' . $this->esc($title) . '">',
            '<meta property="og:type" content="' . $this->esc($ogType) . '">',
        ];

        if ($description !== '') {
            $parts[] = '<meta name="description" content="' . $this->esc($description) . '">';
            $parts[] = '<meta property="og:description" content="' . $this->esc($description) . '">';
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
        }

        return implode("\n", $parts);
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
