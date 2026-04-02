<?php
declare(strict_types=1);

namespace App\Router;

use App\Auth\Auth;
use App\Auth\Login;
use App\Db\Connection;
use App\Db\Query;

final class Router
{
    private string $rootPath;
    private string $basePath;

    public function __construct()
    {
        $this->rootPath = dirname(__DIR__, 3);
        $this->basePath = $this->detectBasePath();
    }

    public function dispatch(string $uri): void
    {
        $rawPath = parse_url($uri, PHP_URL_PATH) ?? '';
        $path = $this->normalizePath($this->stripBasePath((string)$rawPath));

        if ($this->isBlockedPath($path)) {
            $this->notFound();
            return;
        }

        if ($path === '' || $path === 'login') {
            $this->renderPublic($path === '' ? 'index' : 'login');
            return;
        }

        if (str_starts_with($path, 'admin')) {
            $this->handleAdmin($path);
            return;
        }

        $this->renderPublic($path);
    }

    private function handleAdmin(string $path): void
    {
        $auth = new Auth();
        $adminPath = trim(substr($path, 5), '/');

        if ($adminPath === '') {
            $this->redirect($auth->check() ? 'admin/dashboard' : 'admin/login');
        }

        if ($adminPath === 'logout') {
            $auth->logout();
            $this->redirect('admin/login');
        }

        if ($adminPath === 'login') {
            if ($auth->check()) {
                $this->redirect('admin/dashboard');
            }

            $this->renderAdminLogin();
            return;
        }

        if ($adminPath === 'dashboard') {
            if (!$auth->check()) {
                $this->redirect('admin/login');
            }

            $this->render('view/admin/dashboard.php', [
                'user' => $auth->user(),
            ]);
            return;
        }

        $this->notFound();
    }

    private function renderAdminLogin(): void
    {
        $errors = [];
        $message = '';
        $old = [
            'email' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $old['email'] = trim((string)($_POST['email'] ?? ''));

            $login = new Login(new Query(Connection::get()));
            $result = $login->attempt([
                'email' => $old['email'],
                'password' => (string)($_POST['password'] ?? ''),
            ]);

            if (($result['success'] ?? false) === true) {
                $this->redirect('admin/dashboard');
            }

            $errors = $result['errors'] ?? [];
            $message = (string)($result['message'] ?? 'Přihlášení selhalo.');
        }

        $this->render('view/admin/login.php', [
            'errors' => $errors,
            'message' => $message,
            'old' => $old,
        ]);
    }

    private function renderPublic(string $page): void
    {
        $this->render('view/front/' . $page . '.php');
    }

    private function render(string $relativePath, array $data = []): void
    {
        $file = $this->safeFile($relativePath);

        if ($file === null) {
            $this->notFound();
            return;
        }

        $data['url'] = fn(string $path = ''): string => $this->url($path);
        extract($data, EXTR_SKIP);
        require $file;
    }

    private function safeFile(string $relativePath): ?string
    {
        $fullPath = $this->rootPath . '/' . ltrim($relativePath, '/');
        $realPath = realpath($fullPath);

        if ($realPath === false || !str_starts_with($realPath, $this->rootPath . '/view/')) {
            return null;
        }

        return is_file($realPath) ? $realPath : null;
    }

    private function isBlockedPath(string $path): bool
    {
        return $path === 'front' || str_starts_with($path, 'front/') || $path === 'view' || str_starts_with($path, 'view/');
    }

    private function detectBasePath(): string
    {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = trim(dirname($scriptName), '/.');

        return $dir === '' ? '' : '/' . $dir;
    }

    private function stripBasePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');

        if ($this->basePath !== '' && ($normalized === $this->basePath || str_starts_with($normalized, $this->basePath . '/'))) {
            return (string)substr($normalized, strlen($this->basePath));
        }

        return $normalized;
    }

    private function normalizePath(string $path): string
    {
        return trim($path, '/');
    }

    private function url(string $path = ''): string
    {
        $cleanPath = trim($path, '/');

        if ($cleanPath === '') {
            return $this->basePath === '' ? '/' : $this->basePath . '/';
        }

        return ($this->basePath === '' ? '' : $this->basePath) . '/' . $cleanPath;
    }

    private function redirect(string $to): void
    {
        header('Location: ' . $this->url($to));
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '404';
    }
}
