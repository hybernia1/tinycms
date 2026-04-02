<?php
declare(strict_types=1);

namespace App\View;

use App\Service\FlashService;
use App\Service\Router\Router;
use App\Service\CsrfService;

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
        $layoutFile = $this->resolve('view/layout/' . $layout . '.php', '/view/layout/');

        $url = fn(string $path = ''): string => $this->router->url($path);
        $icon = static function (string $name, string $classes = 'icon') use ($url): string {
            $sprite = htmlspecialchars($url('assets/icons.svg#icon-' . $name), ENT_QUOTES, 'UTF-8');
            $classAttr = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
            return '<svg class="' . $classAttr . '" aria-hidden="true" focusable="false"><use href="' . $sprite . '"></use></svg>';
        };
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);

        if ($layout === 'admin' && !isset($data['adminMenu'])) {
            $data['adminMenu'] = [
                ['label' => 'Dashboard', 'url' => $url('admin/dashboard')],
                ['label' => 'Uživatelé', 'url' => $url('admin/users')],
                ['label' => 'Content', 'url' => '#'],
            ];
        }

        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
        $theme = (string)($_GET['theme'] ?? 'light');
        $data['theme'] = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
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
