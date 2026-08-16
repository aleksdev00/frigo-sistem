<?php

declare(strict_types=1);

use App\Controllers\PublicCatalogController;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Http\Request;
use App\Http\Router;
use App\Repositories\PublicCatalogRepository;
use App\View\View;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');
$config = new Config(['app' => ['name' => 'Frigo Sistem', 'url' => 'https://example.test'], 'database' => require $basePath . '/config/database.php']);
$pdo = (new Database($config))->connect();
$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void { try { $assertion(); echo "PASS: {$name}\n"; } catch (Throwable $e) { $failures[] = $name . ': ' . $e->getMessage(); echo "FAIL: {$name}\n"; } };
$assert = static function (bool $condition, string $message = 'Assertion failed.'): void { if (!$condition) throw new RuntimeException($message); };

$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(4));
    $brandSlug = 'midea-' . $suffix;
    $categorySlug = 'inverter-klima-' . $suffix;
    $insertBrand = $pdo->prepare('INSERT INTO brands (name,slug,seo_title,seo_description,is_active,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())');
    $insertBrand->execute(['Midea ' . $suffix, $brandSlug, null, 'Midea opis', 1]); $brandId = (int) $pdo->lastInsertId();
    $insertBrand->execute(['Hidden brand ' . $suffix, 'hidden-brand-' . $suffix, null, null, 0]); $hiddenBrandId = (int) $pdo->lastInsertId();
    $insertBrand->execute(['Empty brand ' . $suffix, 'empty-brand-' . $suffix, null, null, 1]);
    $insertCategory = $pdo->prepare('INSERT INTO categories (name,slug,description,seo_title,seo_description,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
    $insertCategory->execute(['Inverter klima ' . $suffix, $categorySlug, 'Opis kategorije', null, null, 1]); $categoryId = (int) $pdo->lastInsertId();
    $insertCategory->execute(['Hidden category ' . $suffix, 'hidden-category-' . $suffix, null, null, null, 0]); $hiddenCategoryId = (int) $pdo->lastInsertId();
    $insertCategory->execute(['Empty category ' . $suffix, 'empty-category-' . $suffix, null, null, null, 1]);
    $insertProduct = $pdo->prepare('INSERT INTO products (brand_id,category_id,name,slug,code,short_description,is_featured,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
    for ($i=1; $i<=14; $i++) {
        $name = $i === 1 ? '<script>alert(1)</script> Xtreme Save' : 'Test klima ' . $i . ' ' . $suffix;
        $code = $i === 1 ? 'XTREME-12-' . $suffix : 'MODEL-' . $i;
        $insertProduct->execute([$brandId,$categoryId,$name,'phase7-product-'.$i.'-'.$suffix,$code,'Kratak opis', $i===1?1:0,1]);
        if ($i === 1) $mainProductId = (int) $pdo->lastInsertId();
    }
    $insertProduct->execute([$brandId,$categoryId,'Hidden product '.$suffix,'hidden-product-'.$suffix,'HIDDEN',null,0,0]);
    $insertProduct->execute([$hiddenBrandId,$categoryId,'Invalid relation brand '.$suffix,'invalid-brand-product-'.$suffix,null,null,0,1]);
    $insertProduct->execute([$brandId,$hiddenCategoryId,'Invalid relation category '.$suffix,'invalid-category-product-'.$suffix,null,null,0,1]);
    $insertImage = $pdo->prepare('INSERT INTO product_images (product_id,image_path,alt_text,is_main,sort_order,width,height,created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $insertImage->execute([$mainProductId,'uploads/products/test/fallback.webp','Fallback',0,0,800,600]);
    $insertImage->execute([$mainProductId,'uploads/products/test/main.webp','Main alt',1,9,1000,750]);

    $repo = new PublicCatalogRepository($pdo);
    $controller = new PublicCatalogController($repo, new View($basePath . '/resources/views'), $config);
    $router = new Router();
    $router->get('/klima-uredjaji', [$controller,'index']); $router->get('/brend/{slug}', [$controller,'brand']); $router->get('/kategorija/{slug}', [$controller,'category']);

    $test('Only active products with active relations are listed', static function () use ($assert,$repo,$suffix): void { $r=$repo->paginate(['q'=>$suffix,'brand'=>'','category'=>''],1); $assert($r['total']===14, 'Expected 14 public products.'); });
    $test('Hidden products never appear', static function () use ($assert,$repo): void { $assert($repo->paginate(['q'=>'Hidden product','brand'=>'','category'=>''],1)['total']===0); });
    $test('Search matches name', static function () use ($assert,$repo): void { $assert($repo->paginate(['q'=>'Xtreme Save','brand'=>'','category'=>''],1)['total']===1); });
    $test('Search matches brand', static function () use ($assert,$repo,$suffix): void { $assert($repo->paginate(['q'=>'Midea '.$suffix,'brand'=>'','category'=>''],1)['total']===14); });
    $test('Search matches code or model', static function () use ($assert,$repo,$suffix): void { $assert($repo->paginate(['q'=>'XTREME-12-'.$suffix,'brand'=>'','category'=>''],1)['total']===1); });
    $test('Brand filter works', static function () use ($assert,$repo,$brandSlug): void { $assert($repo->paginate(['q'=>'','brand'=>$brandSlug,'category'=>''],1)['total']===14); });
    $test('Category filter works', static function () use ($assert,$repo,$categorySlug): void { $assert($repo->paginate(['q'=>'','brand'=>'','category'=>$categorySlug],1)['total']===14); });
    $test('Combined search and filters work', static function () use ($assert,$repo,$brandSlug,$categorySlug): void { $assert($repo->paginate(['q'=>'Xtreme','brand'=>$brandSlug,'category'=>$categorySlug],1)['total']===1); });
    $test('Unknown valid filter safely returns no results', static function () use ($assert,$controller): void { $r=$controller->index(new Request('GET','/klima-uredjaji',query:['brand'=>'does-not-exist'])); $assert($r->status===200 && str_contains($r->body,'Nema pronađenih proizvoda')); });
    $test('Pagination uses 12 items and clamps excessive page', static function () use ($assert,$repo,$brandSlug): void { $r=$repo->paginate(['q'=>'','brand'=>$brandSlug,'category'=>''],99999); $assert($r['pages']===2 && $r['page']===2 && count($r['items'])===2); });
    $test('Pagination preserves query state', static function () use ($assert,$controller,$brandSlug): void { $body=$controller->index(new Request('GET','/klima-uredjaji',query:['q'=>'Test','brand'=>$brandSlug] ))->body; $assert(str_contains($body,'q=Test&amp%3Bbrand=')===false && str_contains($body,'q=Test&amp;brand='.$brandSlug.'&amp;page=2')); });
    $test('No-results state is understandable', static function () use ($assert,$controller): void { $assert(str_contains($controller->index(new Request('GET','/klima-uredjaji',query:['q'=>'no-such-product-xyz']))->body,'Promenite pojam pretrage')); });
    $test('Main image is preferred over ordered fallback', static function () use ($assert,$repo,$suffix): void { $r=$repo->paginate(['q'=>'XTREME-12-'.$suffix,'brand'=>'','category'=>''],1); $assert($r['items'][0]['image_path']==='uploads/products/test/main.webp'); });
    $test('Products without images render a placeholder', static function () use ($assert,$controller): void { $assert(str_contains($controller->index(new Request('GET','/klima-uredjaji',query:['q'=>'Test klima 2']))->body,'Slika proizvoda nije dostupna')); });
    $test('Active populated brand landing renders and empty or invalid brand is 404', static function () use ($assert,$router,$brandSlug,$suffix): void { $assert($router->dispatch(new Request('GET','/brend/'.$brandSlug))->status===200); $assert($router->dispatch(new Request('GET','/brend/empty-brand-'.$suffix))->status===404); $assert($router->dispatch(new Request('GET','/brend/nope'))->status===404); });
    $test('Active populated category landing renders and empty or invalid category is 404', static function () use ($assert,$router,$categorySlug,$suffix): void { $assert($router->dispatch(new Request('GET','/kategorija/'.$categorySlug))->status===200); $assert($router->dispatch(new Request('GET','/kategorija/empty-category-'.$suffix))->status===404); $assert($router->dispatch(new Request('GET','/kategorija/nope'))->status===404); });
    $test('Catalog root is indexable and search/filter state is noindex', static function () use ($assert,$controller): void { $assert(str_contains($controller->index(new Request('GET','/klima-uredjaji'))->body,'content="index, follow"')); $assert(str_contains($controller->index(new Request('GET','/klima-uredjaji',query:['q'=>'midea']))->body,'content="noindex, follow"')); });
    $test('Malformed or empty query states remain noindex without warnings', static function () use ($assert,$controller): void { $body=$controller->index(new Request('GET','/klima-uredjaji',query:['q'=>[], 'brand'=>'../bad'] ))->body; $assert(str_contains($body,'content="noindex, follow"')); });
    $test('Catalog output escapes stored content', static function () use ($assert,$controller): void { $body=$controller->index(new Request('GET','/klima-uredjaji',query:['q'=>'Xtreme Save']))->body; $assert(!str_contains($body,'<script>alert(1)</script>') && str_contains($body,'&lt;script&gt;alert(1)&lt;/script&gt;')); });
    $test('Public routes expose no destructive methods', static function () use ($assert,$router): void { $assert($router->dispatch(new Request('POST','/klima-uredjaji'))->status===404); });
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
if ($failures !== []) { fwrite(STDERR, implode(PHP_EOL,$failures).PHP_EOL); exit(1); }
echo "All Phase 7 catalog checks passed.\n";
