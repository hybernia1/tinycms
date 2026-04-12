<?php
declare(strict_types=1);

namespace App\View\Themes;

final class ThemeHelper
{
    private MetaHead $metaHead;

    public function __construct(?MetaHead $metaHead = null)
    {
        $this->metaHead = $metaHead ?? new MetaHead();
    }

    public function metaHead(array $data, callable $absoluteUrl, array $overrides = []): string
    {
        return $this->metaHead->render($this->metaHead->fromViewData($data, $absoluteUrl, $overrides));
    }

    public function renderPicture(array $thumbnail, string $alt, callable $url, array $options = []): string
    {
        $webp = trim((string)($thumbnail['webp'] ?? ''));
        $path = trim((string)($thumbnail['path'] ?? ''));
        if ($webp === '' && $path === '') {
            return '';
        }

        $sources = (array)($thumbnail['webp_sources'] ?? []);
        $srcsetParts = [];
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            $sourcePath = trim((string)($source['path'] ?? ''));
            $sourceWidth = (int)($source['width'] ?? 0);
            if ($sourcePath === '' || $sourceWidth <= 0) {
                continue;
            }
            $srcsetParts[] = $url($sourcePath) . ' ' . $sourceWidth . 'w';
        }

        $sizes = trim((string)($options['sizes'] ?? '(max-width: 900px) 100vw, 900px'));
        $loading = trim((string)($options['loading'] ?? 'lazy'));
        $fetchpriority = trim((string)($options['fetchpriority'] ?? 'auto'));
        $decoding = trim((string)($options['decoding'] ?? 'async'));
        $class = trim((string)($options['class'] ?? ''));
        $pictureClass = $class !== '' ? ' class="' . $this->esc($class) . '"' : '';

        $result = '<picture' . $pictureClass . '>';
        if ($webp !== '') {
            $srcset = $srcsetParts !== [] ? implode(', ', $srcsetParts) : $url($webp);
            $result .= '<source type="image/webp" srcset="' . $this->esc($srcset) . '" sizes="' . $this->esc($sizes) . '">';
        }

        $src = $url($path !== '' ? $path : $webp);
        $result .= '<img src="' . $this->esc($src) . '" alt="' . $this->esc($alt) . '" loading="' . $this->esc($loading) . '" fetchpriority="' . $this->esc($fetchpriority) . '" decoding="' . $this->esc($decoding) . '">';
        $result .= '</picture>';

        return $result;
    }

    public function pagination(array $pagination, string $basePath, callable $url, array $query = []): array
    {
        $page = max(1, (int)($pagination['page'] ?? 1));
        $totalPages = max(1, (int)($pagination['total_pages'] ?? 1));

        return [
            'previous' => $page > 1 ? $url($this->buildPagePath($basePath, $query, $page - 1)) : '',
            'next' => $page < $totalPages ? $url($this->buildPagePath($basePath, $query, $page + 1)) : '',
            'page' => $page,
            'total_pages' => $totalPages,
        ];
    }

    private function buildPagePath(string $basePath, array $query, int $page): string
    {
        $params = [];
        foreach ($query as $key => $value) {
            $name = trim((string)$key);
            $item = trim((string)$value);
            if ($name === '' || $item === '') {
                continue;
            }
            $params[$name] = $item;
        }

        if ($page > 1) {
            $params['page'] = (string)$page;
        }

        if ($params === []) {
            return $basePath;
        }

        return $basePath . '?' . http_build_query($params);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
