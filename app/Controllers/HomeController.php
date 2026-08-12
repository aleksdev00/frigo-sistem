<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Http\Request;
use App\Http\Response;
use App\View\View;

final readonly class HomeController
{
    public function __construct(private View $view, private Config $config)
    {
    }

    public function index(Request $request): Response
    {
        $appName = (string) $this->config->get('app.name', 'Frigo Sistem');

        return Response::html($this->view->render('home', [
            'title' => $appName,
            'appName' => $appName,
        ]));
    }
}
