<?php
declare(strict_types=1);

namespace App\View;

final class MetaHead
{
    public function render(array $meta): string
    {
        $data = $this->normalizeMeta($meta);
        $parts = [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>' . $this->esc($data['title']) . '</title>',
            '<meta name="robots" content="' . $this->esc($data['robots']) . '">',
            '<meta property="og:title" content="' . $this->esc($data['title']) . '">',
            '<meta property="og:type" content="' . $this->esc($data['og_type']) . '">',
            '<meta name="twitter:card" content="' . $this->esc($data['og_image'] !== '' ? 'summary_large_image' : 'summary') . '">',
            '<meta name="twitter:title" content="' . $this->esc($data['title']) . '">',
        ];

        $this->appendMeta($parts, 'name', 'description', $data['description']);
        $this->appendMeta($parts, 'property', 'og:description', $data['description']);
        $this->appendMeta($parts, 'name', 'twitter:description', $data['description']);

        $this->appendMeta($parts, 'name', 'keywords', $data['keywords']);
        $this->appendMeta($parts, 'property', 'og:url', $data['url']);

        $this->appendLink($parts, 'canonical', $data['url']);
        $this->appendLink($parts, 'shortlink', $data['shortlink']);

        $this->appendMeta($parts, 'property', 'og:image', $data['og_image']);
        $this->appendMeta($parts, 'name', 'twitter:image', $data['og_image']);

        $this->appendMeta($parts, 'property', 'article:published_time', $data['published_time']);
        $this->appendMeta($parts, 'property', 'article:modified_time', $data['modified_time']);
        $this->appendMeta($parts, 'property', 'og:site_name', $data['site_name']);
        $this->appendMeta($parts, 'name', 'author', $data['author']);
        $this->appendMeta($parts, 'name', 'theme-color', $data['theme_color']);
        $this->appendMeta($parts, 'property', 'og:logo', $data['logo']);

        if ($data['favicon'] !== '') {
            $this->appendLink($parts, 'icon', $data['favicon'], $this->faviconTypeAttr($data['favicon']));
        }

        foreach ($data['alternate_links'] as $link) {
            $parts[] = $link;
        }

        $jsonLd = $this->jsonLdScript($meta, $data);
        if ($jsonLd !== '') {
            $parts[] = $jsonLd;
        }

        return implode("\n", $parts);
    }

    private function normalizeMeta(array $meta): array
    {
        return [
            'title' => $this->clean((string)($meta['title'] ?? 'TinyCMS')),
            'description' => $this->clean((string)($meta['description'] ?? '')),
            'robots' => $this->clean((string)($meta['robots'] ?? 'index,follow')),
            'keywords' => $this->keywords($meta['keywords'] ?? ''),
            'url' => $this->clean((string)($meta['url'] ?? '')),
            'shortlink' => $this->clean((string)($meta['shortlink'] ?? '')),
            'og_type' => $this->clean((string)($meta['og_type'] ?? 'website')),
            'og_image' => $this->clean((string)($meta['og_image'] ?? '')),
            'published_time' => $this->isoDate((string)($meta['published_time'] ?? '')),
            'modified_time' => $this->isoDate((string)($meta['modified_time'] ?? '')),
            'site_name' => $this->clean((string)($meta['site_name'] ?? '')),
            'author' => $this->clean((string)($meta['author'] ?? '')),
            'theme_color' => $this->clean((string)($meta['theme_color'] ?? '')),
            'favicon' => $this->clean((string)($meta['favicon'] ?? '')),
            'logo' => $this->clean((string)($meta['logo'] ?? '')),
            'search_url_template' => $this->clean((string)($meta['search_url_template'] ?? '')),
            'alternate_links' => $this->alternateLinks($meta['alternate_links'] ?? []),
        ];
    }

    private function appendMeta(array &$parts, string $attribute, string $key, string $content): void
    {
        if ($content === '') {
            return;
        }

        $parts[] = '<meta ' . $attribute . '="' . $this->esc($key) . '" content="' . $this->esc($content) . '">';
    }

    private function appendLink(array &$parts, string $rel, string $href, string $extra = ''): void
    {
        if ($href === '') {
            return;
        }

        $parts[] = '<link rel="' . $this->esc($rel) . '" href="' . $this->esc($href) . '"' . $extra . '>';
    }

    private function alternateLinks(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $href = $this->clean((string)($item['href'] ?? ''));
            if ($href === '') {
                continue;
            }

            $rel = $this->clean((string)($item['rel'] ?? 'alternate'));
            $type = $this->clean((string)($item['type'] ?? ''));
            $title = $this->clean((string)($item['title'] ?? ''));

            $attrs = [
                'rel="' . $this->esc($rel) . '"',
                'href="' . $this->esc($href) . '"',
            ];
            if ($type !== '') {
                $attrs[] = 'type="' . $this->esc($type) . '"';
            }
            if ($title !== '') {
                $attrs[] = 'title="' . $this->esc($title) . '"';
            }

            $result[] = '<link ' . implode(' ', $attrs) . '>';
        }

        return $result;
    }

    private function jsonLdScript(array $meta, array $normalized): string
    {
        $structured = $meta['structured_data'] ?? null;
        if (is_string($structured) && trim($structured) !== '') {
            return '<script type="application/ld+json">' . trim($structured) . '</script>';
        }

        $payload = is_array($structured) ? $structured : $this->defaultStructuredData($normalized);
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
        $title = $meta['title'];
        $description = $meta['description'];
        $url = $meta['url'];
        $image = $meta['og_image'] !== '' ? $meta['og_image'] : $meta['logo'];
        $type = $meta['og_type'];
        $author = $meta['author'];
        $siteName = $meta['site_name'];
        $publishedTime = $meta['published_time'];
        $modifiedTime = $meta['modified_time'];
        $searchUrlTemplate = $meta['search_url_template'];
        $keywords = $meta['keywords'];

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
            if ($schemaType === 'Article') {
                $data['mainEntityOfPage'] = $url;
            }
        }
        if ($image !== '') {
            $data['image'] = $image;
        }
        if ($keywords !== '') {
            $data['keywords'] = $keywords;
        }

        if ($schemaType === 'Article') {
            $data['headline'] = $title;
            if ($publishedTime !== '') {
                $data['datePublished'] = $publishedTime;
            }
            if ($modifiedTime !== '') {
                $data['dateModified'] = $modifiedTime;
            }
            if ($author !== '') {
                $data['author'] = [
                    '@type' => 'Person',
                    'name' => $author,
                ];
            }
            if ($siteName !== '') {
                $data['publisher'] = [
                    '@type' => 'Organization',
                    'name' => $siteName,
                ];
            }

            return $data;
        }

        if ($searchUrlTemplate !== '') {
            $data['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => $searchUrlTemplate,
                'query-input' => 'required name=search_term_string',
            ];
        }

        return $data;
    }

    private function isoDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? '' : gmdate('c', $timestamp);
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

    private function faviconTypeAttr(string $favicon): string
    {
        $path = parse_url($favicon, PHP_URL_PATH);
        $extension = strtolower(pathinfo((string)$path, PATHINFO_EXTENSION));

        return match ($extension) {
            'svg' => ' type="image/svg+xml"',
            'png' => ' type="image/png"',
            'ico' => ' type="image/x-icon"',
            default => '',
        };
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
