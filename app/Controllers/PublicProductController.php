<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Foundation\Logger;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicProductRepository;
use App\Services\ProductSeoService;
use App\Services\ProductViewService;
use App\Services\SeoService;
use App\View\View;
use Throwable;

final readonly class PublicProductController
{
    public function __construct(
        private PublicProductRepository $products,
        private ProductViewService $views,
        private ProductSeoService $seo,
        private View $view,
        private Config $config,
        private Logger $logger,
    ) {}

    public function show(Request $request): Response
    {
        $slug = (string) ($request->attributes['slug'] ?? '');
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) !== 1) {
            return $this->notFound();
        }
        $product = $this->products->findBySlug($slug);
        if ($product === null) {
            return $this->notFound();
        }

        try {
            $token = session_id();
            if ($token !== '') {
                $this->views->recordView((int) $product['id'], ['visitor_token' => $token]);
            }
        } catch (Throwable $exception) {
            $this->logger->error('Public product view recording failed.', [
                'product_id' => (int) $product['id'],
                'exception' => $exception::class,
            ]);
        }

        $images = $this->products->images((int) $product['id']);
        $seo = new SeoService($this->config);
        $path = '/klima-uredjaji/' . rawurlencode($slug);
        $canonical = $seo->url($path);
        $structuredProduct = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => (string) $product['name'], 'url' => $canonical];
        if (trim((string) $product['brand_name']) !== '') $structuredProduct['brand'] = ['@type' => 'Brand', 'name' => (string) $product['brand_name']];
        if (trim((string) ($product['code'] ?? '')) !== '') $structuredProduct['sku'] = (string) $product['code'];
        $schemaDescription = trim((string) ($product['short_description'] ?? '')) ?: trim((string) ($product['description'] ?? ''));
        if ($schemaDescription !== '') $structuredProduct['description'] = $schemaDescription;
        if ($images !== []) $structuredProduct['image'] = array_map(fn (array $image): string => $seo->url('/' . ltrim((string) $image['image_path'], '/')), $images);

        $breadcrumbs = [
            ['name' => 'Početna', 'path' => '/'],
            ['name' => 'Klima uređaji', 'path' => '/klima-uredjaji'],
            ['name' => (string) $product['category_name'], 'path' => '/kategorija/' . rawurlencode((string) $product['category_slug'])],
            ['name' => (string) $product['name'], 'path' => $path],
        ];

        $title = $this->seo->title($product);
        $description = $this->seo->description($product);
        $metadata = $seo->page($path, $title, $description, $request->query === [], 'product', $images[0]['image_path'] ?? null);

        return Response::html($this->view->render('catalog/show', [
            ...$metadata,
            'appName' => (string) $this->config->get('app.name', 'Frigo Sistem'),
            'product' => $product,
            'images' => $images,
            'specifications' => $this->products->specifications((int) $product['id']),
            'relatedProducts' => $this->products->related((int) $product['id'], (string) $product['category_slug'], (string) $product['brand_slug']),
            'structuredData' => [$structuredProduct, $seo->breadcrumbs($breadcrumbs)],
            'pageScript' => '/assets/js/product-gallery.js',
        ]));
    }

    private function notFound(): Response
    {
        return Response::html($this->view->render('errors/404', [
            'title' => 'Stranica nije pronađena | Frigo Sistem',
            'metaDescription' => 'Tražena stranica nije pronađena.',
            'robots' => 'noindex, follow',
            'appName' => (string) $this->config->get('app.name', 'Frigo Sistem'),
        ]), 404);
    }
}
