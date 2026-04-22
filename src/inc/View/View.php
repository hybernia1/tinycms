<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\Flash;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Csrf;
use App\Service\Support\Date;
use App\Service\Support\I18n;
use App\Service\Support\Media;
use App\Service\Support\RequestContext;

final class View
{
    private string $rootPath;
    private Router $router;
    private Flash $flash;
    private Csrf $csrf;
    private Date $dateTimeFormatter;

    public function __construct(string $rootPath, Router $router, Flash $flash, Csrf $csrf, Date $dateTimeFormatter)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->router = $router;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    public function render(string $layout, string $template, array $data = []): void
    {
        $this->renderFiles(
            $this->resolve(VIEW_DIR . $template . '.php', VIEW_DIR),
            $this->resolve(VIEW_DIR . $layout . '.php', VIEW_DIR),
            $layout,
            $data
        );
    }

    private function renderFiles(string $templateFile, string $layoutFile, string $layout, array $data = []): void
    {
        $url = fn(string $path = ''): string => $this->router->url($path);
        $e = static fn(mixed $value): string => self::escape($value);
        $absoluteUrl = static function (string $path = '') use ($url): string {
            $value = trim($path);
            if ($value === '') {
                $value = '/';
            }

            if (preg_match('#^https?://#i', $value) === 1) {
                return $value;
            }

            $resolved = $url($value);
            if (!RequestContext::hasAuthority()) {
                return $resolved;
            }

            return RequestContext::scheme() . '://' . RequestContext::authority() . $resolved;
        };
        $icon = static fn(string $name, string $classes = 'icon'): string => self::iconMarkup($url, $name, $classes, true);
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);
        $formatDate = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDate($value, $fallback);
        $formatDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDateTime($value, $fallback);
        $formatInputDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->toInputDateTimeLocal($value, $fallback);
        $t = static fn(string $key, ?string $fallback = null): string => I18n::t($key, $fallback);
        $media = static fn(string $path = '', string $size = 'origin'): string => Media::bySize($path, $size);

        $isAdminLayout = str_starts_with($layout, 'admin/');

        if ($isAdminLayout && is_array($data['adminMenu'] ?? null)) {
            $data['adminMenu'] = array_map(static function (array $item) use ($url): array {
                $path = (string)($item['url'] ?? '');
                return [
                    'label' => (string)($item['label'] ?? ''),
                    'url' => str_starts_with($path, 'http') ? $path : $url($path),
                    'path' => $path,
                    'icon' => (string)($item['icon'] ?? ''),
                ];
            }, $data['adminMenu']);
        }

        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
        $data['icon'] = $icon;
        $data['e'] = $e;
        $data['csrfField'] = $csrfField;
        $data['formatDate'] = $formatDate;
        $data['formatDateTime'] = $formatDateTime;
        $data['formatInputDateTime'] = $formatInputDateTime;
        $data['absoluteUrl'] = $absoluteUrl;
        $data['t'] = $t;
        $data['media'] = $media;
        $data['lang'] = I18n::htmlLang();
        $data['flashes'] = $this->flash->consume();
        $data['currentRoute'] = $this->router->requestPath((string)($_SERVER['REQUEST_URI'] ?? '/'));
        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = (string)ob_get_clean();

        require $layoutFile;
    }

    private function resolve(string $relativePath, string $allowedPath): string
    {
        return self::resolveFile($this->rootPath, $relativePath, $allowedPath);
    }

    public static function resolveFile(string $rootPath, string $relativePath, string $allowedPath): string
    {
        $normalizedRootPath = rtrim($rootPath, '/');
        $fullPath = $normalizedRootPath . '/' . ltrim($relativePath, '/');
        $allowedRoot = rtrim($normalizedRootPath . '/' . trim($allowedPath, '/'), '/');
        $realPath = realpath($fullPath);
        $allowedRealPath = realpath($allowedRoot);
        $normalizedRealPath = $realPath === false ? '' : str_replace('\\', '/', $realPath);
        $normalizedAllowedRoot = $allowedRealPath === false ? '' : str_replace('\\', '/', $allowedRealPath);

        if ($normalizedRealPath === '' || $normalizedAllowedRoot === '' || !str_starts_with($normalizedRealPath, $normalizedAllowedRoot) || !is_file($realPath)) {
            http_response_code(404);
            exit('404');
        }

        return $realPath;
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function iconMarkup(callable $url, string $name, string $classes = 'icon', bool $focusable = false): string
    {
        $sprite = self::escape($url(ASSETS_DIR . 'svg/icons.svg#icon-' . trim($name)));
        $className = trim($classes);
        $focusableAttr = $focusable ? ' focusable="false"' : '';
        return '<svg class="' . self::escape($className !== '' ? $className : 'icon') . '" aria-hidden="true"' . $focusableAttr . '><use href="' . $sprite . '"></use></svg>';
    }
}
