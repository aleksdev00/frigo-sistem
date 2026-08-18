<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Foundation\Config;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PublicCatalogRepository;
use App\Services\SeoService;
use App\View\View;

final readonly class PublicCatalogController
{
    private const SEARCH_MAX = 100;
    public function __construct(private PublicCatalogRepository $catalog, private View $view, private Config $config) {}
    public function index(Request $request): Response { return $this->listing($request, $this->filters($request), 'Klima uređaji', '/klima-uredjaji', 'catalog'); }
    public function brand(Request $request): Response
    {
        $slug = (string) ($request->attributes['slug'] ?? ''); $brand = $this->catalog->findPublicBrand($slug);
        return $brand === null ? $this->notFound() : $this->listing($request, ['q'=>'','brand'=>$slug,'category'=>''], (string) $brand['name'], '/brend/'.$slug, 'brand', $brand);
    }
    public function category(Request $request): Response
    {
        $slug = (string) ($request->attributes['slug'] ?? ''); $category = $this->catalog->findPublicCategory($slug);
        return $category === null ? $this->notFound() : $this->listing($request, ['q'=>'','brand'=>'','category'=>$slug], (string) $category['name'], '/kategorija/'.$slug, 'category', $category);
    }
    private function listing(Request $request, array $filters, string $heading, string $path, string $type, ?array $taxonomy = null): Response
    {
        $result = $this->catalog->paginate($filters, $this->page($request->query['page'] ?? 1));
        $title = match ($type) {
            'brand' => trim((string)($taxonomy['seo_title'] ?? '')) ?: $heading.' klima uređaji | Frigo Sistem',
            'category' => trim((string)($taxonomy['seo_title'] ?? '')) ?: $heading.' klima uređaji | Frigo Sistem',
            default => 'Klima uređaji Niš | Frigo Sistem',
        };
        $description = trim((string)($taxonomy['seo_description'] ?? '')) ?: match ($type) {
            'brand' => 'Pogledajte '.$heading.' klima uređaje u ponudi Frigo Sistema i pošaljite upit za informacije i ugradnju.',
            'category' => 'Pogledajte '.$heading.' klima uređaje u ponudi Frigo Sistema i pronađite uređaj za svoj prostor.',
            default => 'Pogledajte ponudu klima uređaja u Nišu. Pretražite inverter klime po brendu i kategoriji i pošaljite upit Frigo Sistemu.',
        };
        $seo = new SeoService($this->config); $crumbs = [['name'=>'Početna','path'=>'/']];
        if ($type !== 'catalog') $crumbs[] = ['name'=>'Klima uređaji','path'=>'/klima-uredjaji'];
        $crumbs[] = ['name'=>$heading,'path'=>$path];
        return Response::html($this->view->render('catalog/index', [
            ...$seo->page($path, $title, $description, $request->query === []), 'structuredData'=>[$seo->breadcrumbs($crumbs)],
            'appName'=>(string)$this->config->get('app.name','Frigo Sistem'), 'heading'=>$heading, 'type'=>$type, 'taxonomy'=>$taxonomy,
            'filters'=>$filters, 'result'=>$result, 'brands'=>$this->catalog->activeBrands(), 'categories'=>$this->catalog->activeCategories(), 'basePath'=>$path,
        ]));
    }
    private function filters(Request $request): array
    {
        $q = is_string($request->query['q'] ?? null) ? trim($request->query['q']) : '';
        $q = function_exists('mb_substr') ? mb_substr($q,0,self::SEARCH_MAX) : substr($q,0,self::SEARCH_MAX);
        return ['q'=>$q,'brand'=>$this->slug($request->query['brand']??''),'category'=>$this->slug($request->query['category']??'')];
    }
    private function slug(mixed $value): string { $value=is_string($value)?strtolower(trim($value)):''; return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$value)===1?$value:''; }
    private function page(mixed $value): int { if(is_int($value))return max(1,min(10000,$value)); return is_string($value)&&preg_match('/^[1-9][0-9]{0,4}$/D',$value)===1?min(10000,(int)$value):1; }
    private function notFound(): Response { return Response::html($this->view->render('errors/404',['title'=>'Stranica nije pronađena | Frigo Sistem','metaDescription'=>'Tražena stranica nije pronađena.','robots'=>'noindex, follow','appName'=>(string)$this->config->get('app.name','Frigo Sistem')]),404); }
}
