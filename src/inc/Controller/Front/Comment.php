<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Application\Comment as CommentService;
use App\Service\Auth\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\RequestContext;

final class Comment
{
    public function __construct(
        private Auth $auth,
        private CommentService $comments,
        private Csrf $csrf
    ) {
    }

    public function add(callable $redirect, int $contentId): void
    {
        if (!$this->auth->check()) {
            $redirect('auth/login');
            return;
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->redirectBack($redirect);
            return;
        }

        $this->comments->save($contentId, (int)($this->auth->id() ?? 0), $_POST, $this->ipAddress());
        $this->redirectBack($redirect);
    }

    private function redirectBack(callable $redirect): void
    {
        $path = trim((string)($_POST['return'] ?? ''));
        $redirect($this->localPath($path));
    }

    private function localPath(string $path): string
    {
        if ($path === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path) === 1 || str_starts_with($path, '//')) {
            return '';
        }

        $parts = parse_url($path);
        if (!is_array($parts)) {
            return '';
        }

        parse_str((string)($parts['query'] ?? ''), $query);
        $fragment = trim((string)($parts['fragment'] ?? ''));
        if (isset($query['route'])) {
            $route = trim((string)$query['route'], '/');
            unset($query['route']);
            return $this->withSuffix($route, $query, $fragment);
        }

        $basePath = RequestContext::basePath();
        $path = (string)($parts['path'] ?? '');
        if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }

        return str_starts_with($path, '\\') ? '' : $this->withSuffix(ltrim($path, '/'), $query, $fragment);
    }

    private function withSuffix(string $path, array $query, string $fragment): string
    {
        $queryString = http_build_query($query);
        return $path . ($queryString !== '' ? '?' . $queryString : '') . ($fragment !== '' ? '#' . $fragment : '');
    }

    private function ipAddress(): string
    {
        return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}
