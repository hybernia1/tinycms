<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Media;

final class Theme
{
    private string $theme;

    public function __construct(private Router $router, private array $settings, string $theme)
    {
        $this->theme = trim($theme) !== '' ? trim($theme) : 'default';
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

    public function postThumbnail(array $item, array $options = []): string
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

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
