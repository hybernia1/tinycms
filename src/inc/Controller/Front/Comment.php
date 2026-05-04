<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Application\Comment as CommentService;
use App\Service\Auth\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\RequestContext;
use App\Service\Support\RateLimiter;

final class Comment
{
    public function __construct(
        private Auth $auth,
        private CommentService $comments,
        private Csrf $csrf,
        private RateLimiter $rateLimiter,
        private array $settings = []
    ) {
    }

    public function add(callable $redirect, int $contentId): void
    {
        $isAuthenticated = $this->auth->check();
        if (!$isAuthenticated && !$this->anonymousAllowed()) {
            $redirect('auth/login');
            return;
        }

        if (!$this->guardRateLimit($contentId)) {
            $this->redirectBack($redirect);
            return;
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->redirectBack($redirect);
            return;
        }

        $result = $this->comments->save($contentId, $isAuthenticated ? (int)($this->auth->id() ?? 0) : 0, $_POST, $this->ipAddress(), $this->anonymousAllowed());
        if (!$isAuthenticated && ($result['success'] ?? false) === true) {
            $this->rememberPendingComment($contentId, (int)($result['id'] ?? 0));
        }
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

    private function guardRateLimit(int $contentId): bool
    {
        $rateLimit = $this->rateLimiter->hit('comment|' . $contentId . '|' . $this->ipAddress(), 5, 300);
        return ($rateLimit['allowed'] ?? false) === true;
    }

    private function anonymousAllowed(): bool
    {
        return (string)($this->settings['comments_allow_anonymous'] ?? '0') === '1';
    }

    private function rememberPendingComment(int $contentId, int $commentId): void
    {
        if ($contentId <= 0 || $commentId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $key = (string)$contentId;
        $current = array_map(
            static fn(mixed $id): int => (int)$id,
            (array)($_SESSION['pending_comments'][$key] ?? [])
        );
        $current[] = $commentId;
        $current = array_values(array_unique(array_filter($current, static fn(int $id): bool => $id > 0)));

        $_SESSION['pending_comments'][$key] = array_slice($current, -50);
    }
}
