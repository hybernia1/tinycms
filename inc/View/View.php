<?php
declare(strict_types=1);

namespace App\View;

use App\Service\FlashService;
use App\Service\Router\Router;

final class View
{
    private string $rootPath;
    private Router $router;
    private FlashService $flash;

    public function __construct(string $rootPath, Router $router, FlashService $flash)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->router = $router;
        $this->flash = $flash;
    }

    public function render(string $layout, string $template, array $data = []): void
    {
        $templateFile = $this->resolve('view/' . $template . '.php', '/view/');
        $layoutFile = $this->resolve('view/layout/' . $layout . '.php', '/view/layout/');

        $url = fn(string $path = ''): string => $this->router->url($path);

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
