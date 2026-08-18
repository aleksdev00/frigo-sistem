<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicCatalogRepository;
use App\Services\SeoService;
use App\View\View;

final readonly class HomeController
{
    public function __construct(private PublicCatalogRepository $catalog, private View $view, private Config $config) {}

    public function index(Request $request): Response
    {
        $appName = (string) $this->config->get('app.name', 'Frigo Sistem');
        $seo = new SeoService($this->config);
        return Response::html($this->view->render('home', [
            ...$seo->page('/', 'Klima uređaji Niš | Prodaja, ugradnja i servis', 'Frigo Sistem Niš — prodaja, ugradnja, servis i održavanje klima uređaja. Pogledajte ponudu klima uređaja i pošaljite upit.'),
            'appName' => $appName,
            'structuredData' => [['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $appName, 'url' => $seo->url('/')]],
            'featuredProducts' => $this->catalog->featuredProducts(),
            'brands' => $this->catalog->activeBrands(),
        ]));
    }
}
