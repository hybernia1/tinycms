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

    public function renderPartial(string $template, array $data = []): string
    {
        return $this->renderTemplate($this->resolve(VIEW_DIR . $template . '.php', VIEW_DIR), $this->viewData('', $data, false));
    }

    private function renderFiles(string $templateFile, string $layoutFile, string $layout, array $data = []): void
    {
        $viewData = $this->viewData($layout, $data, true);
        $contentHtml = $this->renderTemplate($templateFile, $viewData);
        extract($viewData, EXTR_SKIP);
        $content = $contentHtml;

        require $layoutFile;
    }

    private function viewData(string $layout, array $data, bool $consumeFlashes): array
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
            if (!RequestContext::hasAuthority()) {
                return $resolved;
            }

            return RequestContext::scheme() . '://' . RequestContext::authority() . $resolved;
        };
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);
        $formatDate = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDate($value, $fallback);
        $formatDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDateTime($value, $fallback);
        $formatInputDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->toInputDateTimeLocal($value, $fallback);
        $media = static fn(string $path = '', string $size = 'origin'): string => Media::bySize($path, $size);

        $isAdminLayout = str_starts_with($layout, 'admin/');

        if ($isAdminLayout && is_array($data['adminMenu'] ?? null)) {
            $normalizeAdminMenuItem = static function (array $item) use ($url, &$normalizeAdminMenuItem): array {
                $path = (string)($item['url'] ?? '');
                $normalized = [
                    'label' => (string)($item['label'] ?? ''),
                    'url' => str_starts_with($path, 'http') ? $path : $url($path),
                    'path' => $path,
                    'icon' => (string)($item['icon'] ?? ''),
                ];

                if (is_array($item['children'] ?? null)) {
                    $normalized['children'] = array_map($normalizeAdminMenuItem, $item['children']);
                }

                return $normalized;
            };

            $data['adminMenu'] = array_map($normalizeAdminMenuItem, $data['adminMenu']);
        }

        $data['url'] = $url;
        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
        $data['csrfField'] = $csrfField;
        $data['formatDate'] = $formatDate;
        $data['formatDateTime'] = $formatDateTime;
        $data['formatInputDateTime'] = $formatInputDateTime;
        $data['absoluteUrl'] = $absoluteUrl;
        $data['media'] = $media;
        $data['lang'] = I18n::htmlLang();
        $data['flashes'] = $consumeFlashes ? $this->flash->consume() : [];
        $data['currentRoute'] = $this->router->requestPath((string)($_SERVER['REQUEST_URI'] ?? '/'));

        return $data;
    }

    private function renderTemplate(string $templateFile, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        return (string)ob_get_clean();
    }

    private function resolve(string $relativePath, string $allowedPath): string
    {
        $fullPath = $this->rootPath . '/' . ltrim($relativePath, '/');
        $allowedRoot = rtrim($this->rootPath . '/' . trim($allowedPath, '/'), '/');
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
}
