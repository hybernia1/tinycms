<?php
declare(strict_types=1);

namespace App\Service\Infrastructure\Router;

use App\Service\Support\RequestContext;

final class Router
{
    private string $basePath;
    private array $routes = [];
    private array $dynamicRoutes = [];
    private bool $queryMode;

    public function __construct(string $basePath = '', bool $queryMode = false)
    {
        $clean = trim($basePath, '/');
        $this->basePath = $clean === '' ? '' : '/' . $clean;
        $this->queryMode = $queryMode;
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
        $normalizedPath = $this->normalizePath($path);
        $methodKey = strtoupper($method);

        if (str_contains($normalizedPath, '{')) {
            [$regex, $params] = $this->compilePattern($normalizedPath);
            $this->dynamicRoutes[$methodKey][] = ['regex' => $regex, 'params' => $params, 'handler' => $handler];
            return;
        }

        $this->routes[$methodKey][$normalizedPath] = $handler;
    }

    public function dispatch(string $uri, string $method): bool
    {
        $routePath = $this->requestPath($uri);
        $methodKey = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
        $handler = $this->routes[$methodKey][$routePath] ?? null;

        if ($handler === null) {
            foreach ($this->dynamicRoutes[$methodKey] ?? [] as $route) {
                if (preg_match($route['regex'], $routePath, $matches) !== 1) {
                    continue;
                }

                $params = [];
                foreach ($route['params'] as $name) {
                    $params[$name] = (string)($matches[$name] ?? '');
                }

                $route['handler']($params);
                return true;
            }

            return false;
        }

        $handler();
        return true;
    }

    public function url(string $path = ''): string
    {
        return RequestContext::path($path, $this->basePath, $this->queryMode);
    }

    public function canonicalUrl(string $uri, string $method): ?string
    {
        if (!in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            return null;
        }

        $parts = parse_url($uri);
        $path = '/' . ltrim((string)($parts['path'] ?? ''), '/');
        if ($this->isStaticPath($path)) {
            return null;
        }

        parse_str((string)($parts['query'] ?? ''), $query);
        $hasRoute = array_key_exists('route', $query);
        $route = $this->normalizePath((string)($query['route'] ?? ''));
        unset($query['route']);

        $target = $this->queryMode
            ? $this->fallbackCanonicalUrl($path, $route, $query, $hasRoute)
            : $this->prettyCanonicalUrl($path, $route, $query, $hasRoute);

        return $target !== null && $target !== $this->currentUrl($parts) ? $target : null;
    }

    public function requestPath(string $uri): string
    {
        $parts = parse_url($uri);
        $raw = (string)($parts['path'] ?? '');
        $normalized = '/' . ltrim($raw, '/');

        if ($this->basePath !== '' && ($normalized === $this->basePath || str_starts_with($normalized, $this->basePath . '/'))) {
            $normalized = (string)substr($normalized, strlen($this->basePath));
        }

        $path = $this->normalizePath($normalized);
        if (!$this->queryMode) {
            return $path;
        }

        if ($path !== '' && $path !== 'index.php') {
            return $path;
        }

        parse_str((string)($parts['query'] ?? ''), $query);
        return $this->normalizePath((string)($query['route'] ?? ''));
    }

    private function normalizePath(string $path): string
    {
        return trim($path, '/');
    }

    private function prettyCanonicalUrl(string $path, string $route, array $query, bool $hasRoute): ?string
    {
        $indexPath = $this->basePath . '/index.php';

        if ($hasRoute) {
            return $this->withQuery($this->url($route), $query);
        }

        if ($path === $indexPath) {
            return $this->withQuery($this->url(''), $query);
        }

        if (str_starts_with($path, $indexPath . '/')) {
            return $this->withQuery($this->url(substr($path, strlen($indexPath) + 1)), $query);
        }

        return null;
    }

    private function fallbackCanonicalUrl(string $path, string $route, array $query, bool $hasRoute): ?string
    {
        if ($hasRoute) {
            return $this->withQuery($this->url($route), $query);
        }

        if ($path === $this->basePath) {
            return $this->withQuery($this->url(''), $query);
        }

        if (str_starts_with($path, $this->basePath . '/')) {
            return $this->withQuery($this->url(substr($path, strlen($this->basePath) + 1)), $query);
        }

        $routePath = $this->normalizePath($this->stripStaticBase($path));
        return $this->withQuery($this->url($routePath), $query);
    }

    private function withQuery(string $url, array $query): string
    {
        $queryString = http_build_query($query);
        return $queryString === '' ? $url : $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }

    private function currentUrl(array $parts): string
    {
        $path = '/' . ltrim((string)($parts['path'] ?? ''), '/');
        $query = (string)($parts['query'] ?? '');
        return $path . ($query === '' ? '' : '?' . $query);
    }

    private function isStaticPath(string $path): bool
    {
        if (!defined('BASE_DIR')) {
            return false;
        }
        if ($path === $this->basePath || $path === $this->basePath . '/index.php') {
            return false;
        }

        $relativePath = ltrim($this->stripStaticBase($path), '/');
        return $relativePath !== '' && is_file(BASE_DIR . '/' . $relativePath);
    }

    private function stripStaticBase(string $path): string
    {
        $base = $this->staticBasePath();
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            return (string)substr($path, strlen($base));
        }

        return $path;
    }

    private function staticBasePath(): string
    {
        if (str_ends_with($this->basePath, '/index.php')) {
            return rtrim(substr($this->basePath, 0, -10), '/');
        }

        return $this->basePath;
    }

    private function compilePattern(string $path): array
    {
        if ($path === '') {
            return ['#^$#', []];
        }

        $params = [];
        $segments = explode('/', $path);
        $parts = [];

        foreach ($segments as $segment) {
            if (!str_contains($segment, '{')) {
                $parts[] = preg_quote($segment, '#');
                continue;
            }

            $segmentRegex = '';
            $offset = 0;

            if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $segment, $matches, PREG_OFFSET_CAPTURE) === 0) {
                $parts[] = preg_quote($segment, '#');
                continue;
            }

            foreach ($matches[1] as $index => $match) {
                $name = (string)$match[0];
                $tokenOffset = (int)$matches[0][$index][1];
                $tokenLength = strlen((string)$matches[0][$index][0]);
                $literal = substr($segment, $offset, $tokenOffset - $offset);
                if ($literal !== false && $literal !== '') {
                    $segmentRegex .= preg_quote($literal, '#');
                }
                $params[] = $name;
                $segmentRegex .= '(?<' . $name . '>[^/]+)';
                $offset = $tokenOffset + $tokenLength;
            }

            $tail = substr($segment, $offset);
            if ($tail !== false && $tail !== '') {
                $segmentRegex .= preg_quote($tail, '#');
            }

            $parts[] = $segmentRegex;
        }

        return ['#^' . implode('/', $parts) . '$#', $params];
    }
}
