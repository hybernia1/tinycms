<?php
declare(strict_types=1);

namespace App\Router;

final class Router
{
    public function dispatch(string $uri): void
    {
        $path = trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');
        $file = $this->resolve($path);

        if ($file === null) {
            http_response_code(404);
            echo '404';
            return;
        }

        require $file;
    }

    private function resolve(string $path): ?string
    {
        if ($path === '') {
            return $this->safeFile('front/index.php');
        }

        if (str_starts_with($path, 'admin')) {
            $adminPath = trim(substr($path, 5), '/');
            $target = $adminPath === '' ? 'dashboard' : $adminPath;
            return $this->safeFile('admin/' . $target . '.php');
        }

        return $this->safeFile('front/' . $path . '.php');
    }

    private function safeFile(string $relativePath): ?string
    {
        $fullPath = dirname(__DIR__, 3) . '/' . $relativePath;
        $realPath = realpath($fullPath);

        if ($realPath === false) {
            return null;
        }

        $rootPath = realpath(dirname(__DIR__, 3));

        if ($rootPath === false || !str_starts_with($realPath, $rootPath)) {
            return null;
        }

        return is_file($realPath) ? $realPath : null;
    }
}
