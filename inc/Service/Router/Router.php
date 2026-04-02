<?php
declare(strict_types=1);

namespace App\Router;

final class Router
{
    private string $basePath;
    private array $routes = [];

    public function __construct(string $basePath = '')
    {
        $clean = trim($basePath, '/');
        $this->basePath = $clean === '' ? '' : '/' . $clean;
    }

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $uri, string $method): bool
    {
        $routePath = $this->requestPath($uri);
        $handler = $this->routes[strtoupper($method)][$routePath] ?? null;

        if ($handler === null) {
            return false;
        }

        $handler();
        return true;
    }

    public function url(string $path = ''): string
    {
        $cleanPath = $this->normalizePath($path);

        if ($cleanPath === '') {
            return $this->basePath === '' ? '/' : $this->basePath . '/';
        }

        return ($this->basePath === '' ? '' : $this->basePath) . '/' . $cleanPath;
    }

    private function requestPath(string $uri): string
    {
        $raw = (string)(parse_url($uri, PHP_URL_PATH) ?? '');
        $normalized = '/' . ltrim($raw, '/');

        if ($this->basePath !== '' && ($normalized === $this->basePath || str_starts_with($normalized, $this->basePath . '/'))) {
            $normalized = (string)substr($normalized, strlen($this->basePath));
        }

        return $this->normalizePath($normalized);
    }

    private function normalizePath(string $path): string
    {
        return trim($path, '/');
    }
}
