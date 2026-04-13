<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\Flash;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Csrf;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\I18n;

final class View
{
    private string $rootPath;
    private Router $router;
    private Flash $flash;
    private Csrf $csrf;
    private DateTimeFormatter $dateTimeFormatter;

    public function __construct(string $rootPath, Router $router, Flash $flash, Csrf $csrf, DateTimeFormatter $dateTimeFormatter)
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
        $e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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
        $icon = static function (string $name, string $classes = 'icon') use ($url, $e): string {
            $sprite = $e($url(ASSETS_DIR . 'svg/icons.svg#icon-' . $name));
            $classAttr = $e($classes);
            return '<svg class="' . $classAttr . '" aria-hidden="true" focusable="false"><use href="' . $sprite . '"></use></svg>';
        };
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);
        $formatDate = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDate($value, $fallback);
        $formatDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDateTime($value, $fallback);
        $formatInputDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->toInputDateTimeLocal($value, $fallback);
        $t = static fn(string $key, ?string $fallback = null): string => I18n::t($key, $fallback);

        $isAdminLayout = str_starts_with($layout, 'admin/');

        if ($isAdminLayout && is_array($data['adminMenu'] ?? null)) {
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
        $data['e'] = $e;
        $data['csrfField'] = $csrfField;
        $data['formatDate'] = $formatDate;
        $data['formatDateTime'] = $formatDateTime;
        $data['formatInputDateTime'] = $formatInputDateTime;
        $data['absoluteUrl'] = $absoluteUrl;
        $data['t'] = $t;
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
        $allowedRoot = rtrim($this->rootPath . '/' . trim($allowedPath, '/'), '/');
        $realPath = realpath($fullPath);

        if ($realPath === false || !str_starts_with($realPath, $allowedRoot) || !is_file($realPath)) {
            http_response_code(404);
            exit('404');
        }

        return $realPath;
    }
}
