<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicCatalogRepository;
use App\View\View;

final readonly class PublicCatalogController
{
    private const SEARCH_MAX = 100;

    public function __construct(private PublicCatalogRepository $catalog, private View $view, private Config $config) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        return $this->listing($request, $filters, 'Klima uređaji', '/klima-uredjaji', 'catalog');
    }

    public function brand(Request $request): Response
    {
        $slug = (string) ($request->attributes['slug'] ?? '');
        $brand = $this->catalog->findPublicBrand($slug);
        if ($brand === null) return $this->notFound();
        return $this->listing($request, ['q' => '', 'brand' => $slug, 'category' => ''], (string) $brand['name'], '/brend/' . $slug, 'brand', $brand);
    }

    public function category(Request $request): Response
    {
        $slug = (string) ($request->attributes['slug'] ?? '');
        $category = $this->catalog->findPublicCategory($slug);
        if ($category === null) return $this->notFound();
        return $this->listing($request, ['q' => '', 'brand' => '', 'category' => $slug], (string) $category['name'], '/kategorija/' . $slug, 'category', $category);
    }

    private function listing(Request $request, array $filters, string $heading, string $path, string $type, ?array $taxonomy = null): Response
    {
        $page = $this->page($request->query['page'] ?? 1);
        $result = $this->catalog->paginate($filters, $page);
        $canonical = rtrim((string) $this->config->get('app.url', 'http://localhost'), '/') . $path;
        $hasQueryState = $request->query !== [];
        $title = match ($type) {
            'brand' => trim((string) ($taxonomy['seo_title'] ?? '')) ?: $heading . ' klima uređaji | Frigo Sistem Niš',
            'category' => trim((string) ($taxonomy['seo_title'] ?? '')) ?: $heading . ' | Frigo Sistem Niš',
            default => 'Klima uređaji | Frigo Sistem Niš',
        };
        $description = trim((string) ($taxonomy['seo_description'] ?? '')) ?: 'Pregledajte aktivnu ponudu klima uređaja Frigo Sistema u Nišu.';

        return Response::html($this->view->render('catalog/index', [
            'title' => $title, 'metaDescription' => $description, 'canonical' => $canonical,
            'robots' => $hasQueryState ? 'noindex, follow' : 'index, follow',
            'appName' => (string) $this->config->get('app.name', 'Frigo Sistem'),
            'heading' => $heading, 'type' => $type, 'taxonomy' => $taxonomy, 'filters' => $filters,
            'result' => $result, 'brands' => $this->catalog->activeBrands(),
            'categories' => $this->catalog->activeCategories(), 'basePath' => $path,
        ]));
    }

    private function filters(Request $request): array
    {
        $rawSearch = $request->query['q'] ?? '';
        $q = is_string($rawSearch) ? trim($rawSearch) : '';
        if (function_exists('mb_substr')) $q = mb_substr($q, 0, self::SEARCH_MAX); else $q = substr($q, 0, self::SEARCH_MAX);
        return ['q' => $q, 'brand' => $this->slug($request->query['brand'] ?? ''), 'category' => $this->slug($request->query['category'] ?? '')];
    }

    private function slug(mixed $value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) === 1 ? $value : '';
    }

    private function page(mixed $value): int
    {
        if (is_int($value)) return max(1, min(10000, $value));
        return is_string($value) && preg_match('/^[1-9][0-9]{0,4}$/D', $value) === 1 ? min(10000, (int) $value) : 1;
    }

    private function notFound(): Response
    {
        return Response::html($this->view->render('errors/404', ['title' => 'Stranica nije pronađena', 'appName' => (string) $this->config->get('app.name', 'Frigo Sistem')]), 404);
    }
}
