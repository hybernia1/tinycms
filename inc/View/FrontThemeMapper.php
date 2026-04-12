<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\SluggerService;
use App\Service\Support\ThumbnailVariants;

final class FrontThemeMapper
{
    private SluggerService $slugger;

    public function __construct(SluggerService $slugger)
    {
        $this->slugger = $slugger;
    }

    public function toPublicListItem(array $item): array
    {
        $id = (int)($item['id'] ?? 0);
        $slug = $this->slugger->slug((string)($item['name'] ?? ''), $id);

        return [
            'id' => $id,
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => $this->plainExcerpt((string)($item['excerpt'] ?? '')),
            'created' => (string)($item['created'] ?? ''),
            'slug' => $slug,
            'url' => $slug,
            'thumbnail' => $this->thumbnailData($item),
        ];
    }

    public function toDetailItem(array $item, string $slug, array $terms): array
    {
        return [
            'slug' => $slug,
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => $this->plainExcerpt((string)($item['excerpt'] ?? '')),
            'body' => (string)($item['body'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
            'thumbnail' => $this->thumbnailData($item),
            'terms' => $this->toPublicTerms($terms),
        ];
    }

    private function toPublicTerms(array $terms): array
    {
        $result = [];
        foreach ($terms as $term) {
            $id = (int)($term['id'] ?? 0);
            $name = trim((string)($term['name'] ?? ''));
            if ($id <= 0 || $name === '') {
                continue;
            }

            $result[] = [
                'id' => $id,
                'name' => $name,
                'slug' => $this->slugger->slug($name, $id),
            ];
        }

        return $result;
    }

    private function plainExcerpt(string $excerpt): string
    {
        $plain = trim(strip_tags($excerpt));
        return preg_replace('/\s+/u', ' ', $plain) ?? '';
    }

    private function thumbnailData(array $item): array
    {
        $path = trim((string)($item['thumbnail_path'] ?? ''));
        $webp = trim((string)($item['thumbnail_path_webp'] ?? ''));

        if ($path === '' && $webp === '') {
            return [];
        }

        return [
            'path' => $path,
            'webp' => $webp,
            'webp_sources' => $this->buildWebpSources($webp),
        ];
    }

    private function buildWebpSources(string $webpPath): array
    {
        if ($webpPath === '') {
            return [];
        }

        $sources = [
            ['path' => $webpPath, 'width' => 1024],
        ];

        foreach (ThumbnailVariants::suffixWidthMap() as $suffix => $width) {
            $variant = ThumbnailVariants::thumbnailPath($webpPath, $suffix);
            if ($variant !== '') {
                $sources[] = ['path' => $variant, 'width' => $width];
            }
        }

        usort($sources, static fn(array $a, array $b): int => ((int)$a['width']) <=> ((int)$b['width']));
        return $sources;
    }
}
