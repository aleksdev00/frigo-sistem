<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicCatalogRepository;
use App\Services\SeoService;

final readonly class SeoInfrastructureController
{
    public function __construct(private PublicCatalogRepository $catalog, private SeoService $seo) {}

    public function sitemap(Request $request): Response
    {
        $paths = ['/', '/klima-uredjaji', '/kontakt'];
        foreach ($this->catalog->sitemapBrands() as $row) $paths[] = '/brend/' . rawurlencode((string) $row['slug']);
        foreach ($this->catalog->sitemapCategories() as $row) $paths[] = '/kategorija/' . rawurlencode((string) $row['slug']);
        foreach ($this->catalog->sitemapProducts() as $row) $paths[] = '/klima-uredjaji/' . rawurlencode((string) $row['slug']);
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($paths as $path) $xml .= '  <url><loc>' . $escape($this->seo->url($path)) . '</loc></url>' . "\n";
        return new Response($xml . '</urlset>' . "\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(Request $request): Response
    {
        $body = $this->seo->isProduction()
            ? "User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: " . $this->seo->url('/sitemap.xml') . "\n"
            : "User-agent: *\nDisallow: /\n";
        return new Response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
