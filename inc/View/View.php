<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\FlashService;
use App\Service\Infra\Router\Router;
use App\Service\Support\CsrfService;

final class View
{
    private string $rootPath;
    private Router $router;
    private FlashService $flash;
    private CsrfService $csrf;

    public function __construct(string $rootPath, Router $router, FlashService $flash, CsrfService $csrf)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->router = $router;
        $this->flash = $flash;
        $this->csrf = $csrf;
    }

    public function render(string $layout, string $template, array $data = []): void
    {
        $templateFile = $this->resolve('view/' . $template . '.php', '/view/');
        $layoutFile = $this->resolve('view/' . $layout . '.php', '/view/');

        $url = fn(string $path = ''): string => $this->router->url($path);
        $icon = static function (string $name, string $classes = 'icon') use ($url): string {
            $sprite = htmlspecialchars($url('assets/icons.svg#icon-' . $name), ENT_QUOTES, 'UTF-8');
            $classAttr = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
            return '<svg class="' . $classAttr . '" aria-hidden="true" focusable="false"><use href="' . $sprite . '"></use></svg>';
        };
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);

        $isAdminLayout = str_starts_with($layout, 'admin/');

        if ($isAdminLayout && !isset($data['adminMenu'])) {
            $data['adminMenu'] = [
                ['label' => 'Dashboard', 'url' => $url('admin/dashboard')],
                ['label' => 'Uživatelé', 'url' => $url('admin/users')],
                ['label' => 'Nastavení', 'url' => $url('admin/settings')],
            ];
        } elseif ($isAdminLayout && isset($data['adminMenu']) && is_array($data['adminMenu'])) {
            $data['adminMenu'] = array_map(static function (array $item) use ($url): array {
                $path = (string)($item['url'] ?? '');
                return [
                    'label' => (string)($item['label'] ?? ''),
                    'url' => str_starts_with($path, 'http') ? $path : $url($path),
                ];
            }, $data['adminMenu']);
        }

        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
        $data['icon'] = $icon;
        $data['csrfField'] = $csrfField;
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
