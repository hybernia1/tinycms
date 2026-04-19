<?php
declare(strict_types=1);

namespace App\Service\Infrastructure\Router;

final class Router
{
    private string $basePath;
    private array $routes = [];
    private array $dynamicRoutes = [];

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
        $handler = $this->routes[strtoupper($method)][$routePath] ?? null;

        if ($handler === null) {
            foreach ($this->dynamicRoutes[strtoupper($method)] ?? [] as $route) {
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
        $cleanPath = $this->normalizePath($path);

        if ($cleanPath === '') {
            return $this->basePath === '' ? '/' : $this->basePath . '/';
        }

        return ($this->basePath === '' ? '' : $this->basePath) . '/' . $cleanPath;
    }

    public function requestPath(string $uri): string
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
