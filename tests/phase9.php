<?php

declare(strict_types=1);

use App\View\View;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void {
    try { $assertion(); echo "PASS: {$name}\n"; }
    catch (Throwable $exception) { $failures[] = $name . ': ' . $exception->getMessage(); echo "FAIL: {$name}\n"; }
};
$assert = static function (bool $condition, string $message = 'Assertion failed.'): void {
    if (!$condition) throw new RuntimeException($message);
};
$view = new View($basePath . '/resources/views');

$product = [
    'id'=>1,'name'=>'Klima <script>alert(1)</script>','slug'=>'bezbedna-klima','brand_name'=>'Midea','brand_slug'=>'midea',
    'category_name'=>'Inverter klima','category_slug'=>'inverter-klima','code'=>'X-12','price'=>'74999.90',
    'short_description'=>'Efikasan uređaj','description'=>'Opis','is_featured'=>1,'image_path'=>null,'image_alt'=>null,
];
$listing = [
    'title'=>'Klima uređaji','appName'=>'Frigo Sistem','heading'=>'Klima uređaji','type'=>'catalog','taxonomy'=>null,
    'filters'=>['q'=>'','brand'=>'','category'=>''],'result'=>['total'=>1,'items'=>[$product],'page'=>1,'pages'=>1],
    'brands'=>[['name'=>'Midea','slug'=>'midea']],'categories'=>[['name'=>'Inverter klima','slug'=>'inverter-klima']],
    'basePath'=>'/klima-uredjaji',
];

$test('Public shell renders semantic branded navigation and footer', static function () use ($assert,$view,$listing):void {
    $_SERVER['REQUEST_URI']='/klima-uredjaji'; $body=$view->render('catalog/index',$listing);
    $assert(str_contains($body,'class="site-header"') && str_contains($body,'aria-label="Glavna navigacija"') && str_contains($body,'FRIGO SISTEM') && str_contains($body,'class="site-footer"'));
});
$test('Mobile navigation has accessible collapsed state and control relationship', static function () use ($assert,$view,$listing):void {
    $body=$view->render('catalog/index',$listing);
    $assert(str_contains($body,'data-nav-toggle') && str_contains($body,'aria-expanded="false"') && str_contains($body,'aria-controls="primary-navigation"'));
});
$test('Catalog renders shared filters, card and safe missing image state', static function () use ($assert,$view,$listing):void {
    $body=$view->render('catalog/index',$listing);
    $assert(str_contains($body,'class="catalog-filters"') && str_contains($body,'class="product-card"') && str_contains($body,'Slika proizvoda nije dostupna') && !str_contains($body,'<script>alert(1)</script>'));
});
$test('Pagination renders active and disabled states without changing query behavior', static function () use ($assert,$view,$listing):void {
    $data=$listing; $data['result']['pages']=3; $data['result']['page']=2; $data['filters']['q']='midea';
    $body=$view->render('catalog/index',$data);
    $assert(str_contains($body,'aria-current="page"') && str_contains($body,'rel="prev"') && str_contains($body,'rel="next"') && str_contains($body,'q=midea'));
});
$test('Product detail renders gallery, specifications, breadcrumbs and Phase 9 CTA', static function () use ($assert,$view,$product):void {
    $body=$view->render('catalog/show',[
        'title'=>'Proizvod','appName'=>'Frigo Sistem','product'=>$product,
        'images'=>[['image_path'=>'uploads/test.webp','alt_text'=>'Uređaj','width'=>800,'height'=>600]],
        'specifications'=>[['name'=>'Wi-Fi','value'=>'Da']], 'relatedProducts'=>[],
        'structuredProduct'=>['@context'=>'https://schema.org','@type'=>'Product','name'=>'Klima'],
        'structuredBreadcrumbs'=>[['name'=>'Početna','url'=>'https://example.test/'],['name'=>'Klima','url'=>'https://example.test/klima']],
        'pageScript'=>'/assets/js/product-gallery.js',
    ]);
    $assert(str_contains($body,'data-gallery-main') && str_contains($body,'class="specification-list"') && str_contains($body,'class="breadcrumbs"') && str_contains($body,'class="cta-panel"') && !str_contains($body,'href="/kontakt'));
});
$test('Public error pages use safe consistent states', static function () use ($assert,$view):void {
    $notFound=$view->render('errors/404',['title'=>'Stranica nije pronađena','appName'=>'Frigo Sistem']);
    $error=$view->render('errors/500',['title'=>'Greška','appName'=>'Frigo Sistem']);
    $assert(str_contains($notFound,'class="error-state"') && str_contains($error,'class="error-state"') && !str_contains($error,'Exception'));
});
$test('Phase 9 assets use centralized tokens and lightweight vanilla scripts', static function () use ($assert,$basePath):void {
    $css=(string)file_get_contents($basePath.'/public/assets/css/app.css'); $js=(string)file_get_contents($basePath.'/public/assets/js/public.js');
    $assert(str_contains($css,'--color-primary: #004a99') && str_contains($css,'--color-accent: #aec8fa') && str_contains($css,'@media (max-width:760px)') && str_contains($js,"event.key === 'Escape'"));
});

if ($failures !== []) { fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL); exit(1); }
echo "All Phase 9 design integration checks passed.\n";
