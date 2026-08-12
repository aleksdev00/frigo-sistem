<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Http\Request;
use App\Http\Response;
use App\Security\Csrf;
use App\Services\AuthService;
use App\View\View;

final readonly class AdminAuthController
{
    public function __construct(
        private View $view,
        private Config $config,
        private AuthService $auth,
        private Csrf $csrf,
    ) {
    }

    public function showLogin(Request $request): Response
    {
        if ($this->auth->isAuthenticated()) {
            return Response::redirect('/admin');
        }
        return $this->loginResponse();
    }

    public function login(Request $request): Response
    {
        if (!$this->csrf->validate($request->input['_csrf'] ?? null)) {
            return $this->loginResponse('The request could not be verified. Please try again.', 419);
        }

        $username = is_string($request->input['username'] ?? null) ? $request->input['username'] : '';
        $password = is_string($request->input['password'] ?? null) ? $request->input['password'] : '';
        if (strlen($username) > 100 || strlen($password) > 1024
            || !$this->auth->attempt($username, $password, $request->clientIp)
        ) {
            return $this->loginResponse('Invalid username or password.', 422, $username);
        }

        $this->csrf->rotate();
        return Response::redirect('/admin');
    }

    public function dashboard(Request $request): Response
    {
        return $this->adminHtml($this->view->render('admin/dashboard', [
            'title' => 'Admin',
            'appName' => (string) $this->config->get('app.name', 'Frigo Sistem'),
            'username' => $this->auth->currentUsername(),
            'csrfToken' => $this->csrf->token(),
        ], 'layouts/admin'));
    }

    public function logout(Request $request): Response
    {
        if (!$this->csrf->validate($request->input['_csrf'] ?? null)) {
            return new Response('The request could not be verified.', 419, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]);
        }
        $this->auth->logout();
        return Response::redirect('/admin/login');
    }

    private function loginResponse(?string $error = null, int $status = 200, string $username = ''): Response
    {
        return $this->adminHtml($this->view->render('admin/login', [
            'title' => 'Admin login',
            'appName' => (string) $this->config->get('app.name', 'Frigo Sistem'),
            'csrfToken' => $this->csrf->token(),
            'error' => $error,
            'username' => $username,
        ], 'layouts/admin'), $status);
    }

    private function adminHtml(string $body, int $status = 200): Response
    {
        return new Response($body, $status, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
