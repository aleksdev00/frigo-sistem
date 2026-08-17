<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicCatalogRepository;
use App\View\View;

final readonly class HomeController
{
    public function __construct(private PublicCatalogRepository $catalog, private View $view, private Config $config)
    {
    }

    public function index(Request $request): Response
    {
        $appName = (string) $this->config->get('app.name', 'Frigo Sistem');
        $baseUrl = rtrim((string) $this->config->get('app.url', 'http://localhost'), '/');

        return Response::html($this->view->render('home', [
            'title' => 'Klima uređaji, montaža i servis | ' . $appName,
            'appName' => $appName,
            'metaDescription' => 'Izbor klima uređaja, stručna montaža, servis i održavanje na jednom mestu.',
            'canonical' => $baseUrl . '/',
            'robots' => 'index, follow',
            'featuredProducts' => $this->catalog->featuredProducts(),
            'brands' => $this->catalog->activeBrands(),
        ]));
    }
}
