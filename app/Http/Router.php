<?php

declare(strict_types=1);

namespace App\Http;

use App\View\View;

final class Router
{
    /** @var array<string, list<array{path: string, pattern: string, handler: callable}>> */
    private array $routes = [];
    private $adminGuard = null;

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $path = Request::normalizePath($path);
        $pattern = preg_quote($path, '#');
        $pattern = preg_replace('#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#', '(?P<$1>[1-9][0-9]*)', $pattern);
        $this->routes[strtoupper($method)][] = [
            'path' => $path,
            'pattern' => '#^' . $pattern . '$#D',
            'handler' => $handler,
        ];
    }

    public function protectAdminWith(callable $guard): void
    {
        $this->adminGuard = $guard;
    }

    public function dispatch(Request $request): Response
    {
        if ($request->path !== '/admin/login'
            && ($request->path === '/admin' || str_starts_with($request->path, '/admin/'))
            && $this->adminGuard !== null
        ) {
            $guardResponse = ($this->adminGuard)($request);
            if ($guardResponse instanceof Response) {
                return $guardResponse;
            }
        }

        $handler = null;
        $routeRequest = $request;
        foreach ($this->routes[$request->method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            $attributes = [];
            foreach ($matches as $name => $value) {
                if (is_string($name)) {
                    $attributes[$name] = (int) $value;
                }
            }
            $handler = $route['handler'];
            $routeRequest = $request->withAttributes($attributes);
            break;
        }

        if ($handler === null) {
            $view = new View(dirname(__DIR__, 2) . '/resources/views');
            return Response::html($view->render('errors/404', ['title' => 'Page not found', 'appName' => 'Frigo Sistem']), 404);
        }

        $response = $handler($routeRequest);
        if (!$response instanceof Response) {
            throw new \LogicException('Route handlers must return an HTTP response.');
        }

        return $response;
    }
}
