<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\FlashService;
use App\Service\Infra\Router\Router;
use App\Service\Support\CsrfService;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\I18n;

final class View
{
    private string $rootPath;
    private Router $router;
    private FlashService $flash;
    private CsrfService $csrf;
    private DateTimeFormatter $dateTimeFormatter;
    private MetaHead $metaHead;

    public function __construct(string $rootPath, Router $router, FlashService $flash, CsrfService $csrf, DateTimeFormatter $dateTimeFormatter)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->router = $router;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->metaHead = new MetaHead();
    }

    public function render(string $layout, string $template, array $data = []): void
    {
        $this->renderFiles(
            $this->resolve('view/' . $template . '.php', '/view/'),
            $this->resolve('view/' . $layout . '.php', '/view/'),
            $layout,
            $data
        );
    }

    public function renderTheme(string $theme, string $template, array $data = []): void
    {
        $base = 'themes/' . trim($theme, '/');
        $this->renderFiles(
            $this->resolve($base . '/' . $template . '.php', '/themes/'),
            $this->resolve($base . '/layout.php', '/themes/'),
            'theme/' . $theme,
            $data
        );
    }

    private function renderFiles(string $templateFile, string $layoutFile, string $layout, array $data = []): void
    {
        $url = fn(string $path = ''): string => $this->router->url($path);
        $absoluteUrl = static function (string $path = '') use ($url): string {
            $value = trim($path);
            if ($value === '') {
                $value = '/';
            }

            if (preg_match('#^https?://#i', $value) === 1) {
                return $value;
            }

            $resolved = $url($value);
            $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
            if ($host === '') {
                return $resolved;
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $host . $resolved;
        };
        $icon = static function (string $name, string $classes = 'icon') use ($url): string {
            $sprite = htmlspecialchars($url('assets/svg/icons.svg#icon-' . $name), ENT_QUOTES, 'UTF-8');
            $classAttr = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
            return '<svg class="' . $classAttr . '" aria-hidden="true" focusable="false"><use href="' . $sprite . '"></use></svg>';
        };
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);
        $formatDate = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDate($value, $fallback);
        $formatDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDateTime($value, $fallback);
        $formatInputDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->toInputDateTimeLocal($value, $fallback);
        $t = static fn(string $key, ?string $fallback = null): string => I18n::t($key, $fallback);
        $renderFrontHead = fn(array $options = []): string => $this->metaHead->render($options);
        $website_title = static fn(): string => trim((string)($data['siteName'] ?? 'TinyCMS'));
        $website_logo = static function () use ($data, $url, $website_title): string {
            $title = htmlspecialchars($website_title(), ENT_QUOTES, 'UTF-8');
            $logoPath = trim((string)($data['siteLogo'] ?? ''));
            if ($logoPath === '') {
                return '<span>' . $title . '</span>';
            }

            $logoUrl = htmlspecialchars($url($logoPath), ENT_QUOTES, 'UTF-8');
            return '<img src="' . $logoUrl . '" alt="' . $title . '">';
        };
        $website_thumbnail = static function (array $thumb = [], string $size = 'original') use ($url): string {
            $path = trim((string)($thumb['path'] ?? ''));
            $webp = trim((string)($thumb['webp'] ?? ''));
            $sources = array_values(array_filter((array)($thumb['webp_sources'] ?? []), static fn($source): bool => is_array($source) && trim((string)($source['path'] ?? '')) !== '' && (int)($source['width'] ?? 0) > 0));

            if ($size === 'original' || $sources === []) {
                $resolved = $path !== '' ? $path : $webp;
                return $resolved !== '' ? $url($resolved) : '';
            }

            preg_match('/\d+/', $size, $match);
            $target = (int)($match[0] ?? 0);
            if ($target <= 0) {
                $resolved = $path !== '' ? $path : $webp;
                return $resolved !== '' ? $url($resolved) : '';
            }

            usort($sources, static fn(array $a, array $b): int => ((int)($a['width'] ?? 0)) <=> ((int)($b['width'] ?? 0)));
            $selected = null;
            foreach ($sources as $source) {
                if ((int)($source['width'] ?? 0) >= $target) {
                    $selected = trim((string)($source['path'] ?? ''));
                    break;
                }
            }
            if ($selected === null) {
                $last = $sources[count($sources) - 1] ?? [];
                $selected = trim((string)($last['path'] ?? ''));
            }

            return $selected !== '' ? $url($selected) : '';
        };
        $website_thumbnail_srcset = static function (array $thumb = []) use ($url): string {
            $parts = [];
            foreach ((array)($thumb['webp_sources'] ?? []) as $source) {
                $sourcePath = trim((string)($source['path'] ?? ''));
                $sourceWidth = (int)($source['width'] ?? 0);
                if ($sourcePath === '' || $sourceWidth <= 0) {
                    continue;
                }
                $parts[] = $url($sourcePath) . ' ' . $sourceWidth . 'w';
            }

            if ($parts !== []) {
                return implode(', ', $parts);
            }

            $fallback = trim((string)($thumb['webp'] ?? ''));
            return $fallback !== '' ? $url($fallback) : '';
        };

        $isAdminLayout = str_starts_with($layout, 'admin/');

        if ($isAdminLayout && !isset($data['adminMenu'])) {
            $data['adminMenu'] = [
                ['label' => I18n::t('admin.menu.dashboard'), 'url' => $url('admin/dashboard'), 'icon' => 'dashboard'],
                ['label' => I18n::t('admin.menu.users'), 'url' => $url('admin/users'), 'icon' => 'users'],
                ['label' => I18n::t('admin.menu.settings'), 'url' => $url('admin/settings'), 'icon' => 'settings'],
            ];
        } elseif ($isAdminLayout && isset($data['adminMenu']) && is_array($data['adminMenu'])) {
            $data['adminMenu'] = array_map(static function (array $item) use ($url): array {
                $path = (string)($item['url'] ?? '');
                return [
                    'label' => (string)($item['label'] ?? ''),
                    'url' => str_starts_with($path, 'http') ? $path : $url($path),
                    'icon' => (string)($item['icon'] ?? ''),
                ];
            }, $data['adminMenu']);
        }

        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
        $data['icon'] = $icon;
        $data['csrfField'] = $csrfField;
        $data['formatDate'] = $formatDate;
        $data['formatDateTime'] = $formatDateTime;
        $data['formatInputDateTime'] = $formatInputDateTime;
        $data['absoluteUrl'] = $absoluteUrl;
        $data['t'] = $t;
        $data['renderFrontHead'] = $renderFrontHead;
        $data['website_title'] = $website_title;
        $data['website_logo'] = $website_logo;
        $data['website_thumbnail'] = $website_thumbnail;
        $data['website_thumbnail_srcset'] = $website_thumbnail_srcset;
        $data['lang'] = I18n::htmlLang();
        $data['flashes'] = $this->flash->consume();
        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = (string)ob_get_clean();

        require $layoutFile;
    }

    private function resolve(string $relativePath, string $allowedPath): string
    {
        $fullPath = $this->rootPath . '/' . ltrim($relativePath, '/');
        $realPath = realpath($fullPath);

        if ($realPath === false || !str_starts_with($realPath, $this->rootPath . $allowedPath) || !is_file($realPath)) {
            http_response_code(404);
            exit('404');
        }

        return $realPath;
    }
}
