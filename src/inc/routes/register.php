<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}


use App\Service\Infrastructure\Router\Router;

if (!function_exists('register_routes')) {
    function register_routes(Router $router, callable $redirect, array $routes): void
    {
        foreach ($routes as $route) {
            $method = strtoupper((string)($route['method'] ?? 'GET'));
            $path = (string)($route['path'] ?? '');
            $customHandler = $route['handler'] ?? null;

            if (is_callable($customHandler)) {
                $handler = $customHandler;
            } else {
                $controller = $route['controller'] ?? null;
                $action = (string)($route['action'] ?? '');
                $withRedirect = (bool)($route['with_redirect'] ?? !str_contains('/' . trim($path, '/') . '/', '/api/'));
                $rawParams = (bool)($route['raw_params'] ?? false);
                $params = (array)($route['params'] ?? []);

                if (!is_object($controller) || $action === '' || !method_exists($controller, $action)) {
                    throw new InvalidArgumentException('Invalid route definition for path: ' . $path);
                }

                $handler = static function (array $routeParams = []) use ($controller, $action, $redirect, $withRedirect, $rawParams, $params): void {
                    $args = [];

                    if ($withRedirect) {
                        $args[] = $redirect;
                    }

                    if ($rawParams) {
                        $args[] = $routeParams;
                    } else {
                        foreach ($params as $name => $type) {
                            $value = (string)($routeParams[$name] ?? '');
                            $args[] = $type === 'int' ? (int)$value : $value;
                        }
                    }

                    $controller->{$action}(...$args);
                };
            }

            if ($method === 'POST') {
                $router->post($path, $handler);
                continue;
            }

            $router->get($path, $handler);
        }
    }
}
