<?php

declare(strict_types=1);

namespace App\Http;

use App\View\View;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];
    private $adminGuard = null;

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][Request::normalizePath($path)] = $handler;
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

        $methodRoutes = $this->routes[$request->method] ?? [];
        $handler = $methodRoutes[$request->path] ?? null;

        if ($handler === null) {
            $view = new View(dirname(__DIR__, 2) . '/resources/views');
            return Response::html($view->render('errors/404', ['title' => 'Page not found', 'appName' => 'Frigo Sistem']), 404);
        }

        $response = $handler($request);
        if (!$response instanceof Response) {
            throw new \LogicException('Route handlers must return an HTTP response.');
        }

        return $response;
    }
}
