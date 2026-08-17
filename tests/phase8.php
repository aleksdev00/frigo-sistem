<?php

declare(strict_types=1);

use App\Controllers\PublicCatalogController;
use App\Controllers\PublicProductController;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Foundation\Logger;
use App\Http\Request;
use App\Http\Router;
use App\Repositories\AnalyticsRepository;
use App\Repositories\PublicCatalogRepository;
use App\Repositories\PublicProductRepository;
use App\Services\ProductSeoService;
use App\Services\ProductViewService;
use App\View\View;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');
$config = new Config(['app' => ['name' => 'Frigo Sistem', 'url' => 'https://example.test'], 'database' => require $basePath . '/config/database.php']);
$pdo = (new Database($config))->connect();
$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void { try { $assertion(); echo "PASS: {$name}\n"; } catch (Throwable $e) { $failures[]=$name.': '.$e->getMessage(); echo "FAIL: {$name}\n"; } };
$assert = static function (bool $condition, string $message='Assertion failed.'): void { if (!$condition) throw new RuntimeException($message); };

if (session_status() !== PHP_SESSION_ACTIVE) { session_id('phase8-' . bin2hex(random_bytes(8))); session_start(); }
$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(4));
    $insertBrand=$pdo->prepare('INSERT INTO brands (name,slug,is_active,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())');
    $insertBrand->execute(['Brand <script>'.$suffix.'</script>','phase8-brand-'.$suffix,1]); $brandId=(int)$pdo->lastInsertId();
    $insertBrand->execute(['Inactive brand','phase8-inactive-brand-'.$suffix,0]); $inactiveBrandId=(int)$pdo->lastInsertId();
    $insertCategory=$pdo->prepare('INSERT INTO categories (name,slug,is_active,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())');
    $insertCategory->execute(['Inverter klima '.$suffix,'phase8-category-'.$suffix,1]); $categoryId=(int)$pdo->lastInsertId();
    $insertCategory->execute(['Inactive category '.$suffix,'phase8-inactive-category-'.$suffix,0]); $inactiveCategoryId=(int)$pdo->lastInsertId();
    $insertProduct=$pdo->prepare('INSERT INTO products (brand_id,category_id,name,slug,code,price,short_description,description,seo_title,seo_description,is_featured,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $slug='phase8-product-'.$suffix;
    $insertProduct->execute([$brandId,$categoryId,'Klima <img src=x>',$slug,'MODEL-'.$suffix,'123456.70','Kratak <b>opis</b>',"Detaljan opis\nDrugi red",'SEO naslov','SEO opis',1,1]); $productId=(int)$pdo->lastInsertId();
    $insertProduct->execute([$brandId,$categoryId,'Related category','phase8-related-category-'.$suffix,null,null,null,null,null,null,0,1]); $relatedCategoryId=(int)$pdo->lastInsertId();
    $insertProduct->execute([$brandId,$categoryId,'Related hidden','phase8-related-hidden-'.$suffix,null,null,null,null,null,null,0,0]);
    $insertProduct->execute([$inactiveBrandId,$categoryId,'Bad brand','phase8-bad-brand-'.$suffix,null,null,null,null,null,null,0,1]);
    $insertProduct->execute([$brandId,$inactiveCategoryId,'Bad category','phase8-bad-category-'.$suffix,null,null,null,null,null,null,0,1]);
    $insertProduct->execute([$brandId,$categoryId,'No optional data','phase8-no-options-'.$suffix,null,null,null,null,null,null,0,1]); $noOptionsSlug='phase8-no-options-'.$suffix;
    $insertImage=$pdo->prepare('INSERT INTO product_images (product_id,image_path,alt_text,is_main,sort_order,width,height,created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $insertImage->execute([$productId,'uploads/products/late.webp','Late <alt>',0,1,800,600]);
    $insertImage->execute([$productId,'uploads/products/main.webp','Main alt',1,9,1200,900]);
    $insertImage->execute([$productId,'uploads/products/first.webp',null,0,0,640,480]);
    $insertSpec=$pdo->prepare('INSERT INTO product_specifications (product_id,name,value,sort_order,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())');
    $insertSpec->execute([$productId,'Druga','Vrednost 2',2]); $insertSpec->execute([$productId,'<Prva>','<script>1</script>',0]);

    $repository=new PublicProductRepository($pdo);
    $analytics=new AnalyticsRepository($pdo);
    $controller=new PublicProductController($repository,new ProductViewService($analytics,str_repeat('phase8-key-',4)) ,new ProductSeoService(),new View($basePath.'/resources/views'),$config,new Logger($basePath.'/storage/logs/phase8-test.log'));
    $router=new Router(); $router->get('/klima-uredjaji/{slug}',[$controller,'show']);

    $test('Active detail returns 200 and renders public product data', static function () use ($assert,$router,$slug):void { $r=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug)); $assert($r->status===200 && str_contains($r->body,'MODEL-') && str_contains($r->body,'123.456,70 RSD') && str_contains($r->body,'Detaljan opis')); });
    $test('Unknown, hidden and inactive taxonomy products return 404', static function () use ($assert,$router,$suffix):void { foreach (['unknown-'.$suffix,'phase8-related-hidden-'.$suffix,'phase8-bad-brand-'.$suffix,'phase8-bad-category-'.$suffix] as $slug) $assert($router->dispatch(new Request('GET','/klima-uredjaji/'.$slug))->status===404,$slug); });
    $test('Malformed slug is rejected by the public router', static function () use ($assert,$router):void { $assert($router->dispatch(new Request('GET','/klima-uredjaji/Bad_Slug'))->status===404); });
    $test('Main image comes first and gallery follows canonical order', static function () use ($assert,$repository,$productId):void { $images=$repository->images($productId); $assert(array_column($images,'image_path')===['uploads/products/main.webp','uploads/products/first.webp','uploads/products/late.webp']); });
    $test('Specifications retain order and unsafe values are escaped', static function () use ($assert,$router,$slug):void { $body=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug))->body; $assert(strpos($body,'&lt;Prva&gt;')<strpos($body,'Druga') && str_contains($body,'&lt;script&gt;1&lt;/script&gt;') && !str_contains($body,'<script>1</script>')); });
    $test('Stored product and taxonomy output is escaped', static function () use ($assert,$router,$slug):void { $body=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug))->body; $assert(!str_contains($body,'<img src=x>') && str_contains($body,'&lt;img src=x&gt;') && !str_contains($body,'<script>'.$slug)); });
    $test('No-image and optional-data fallbacks are safe', static function () use ($assert,$router,$noOptionsSlug):void { $body=$router->dispatch(new Request('GET','/klima-uredjaji/'.$noOptionsSlug))->body; $assert(str_contains($body,'Slika proizvoda nije dostupna') && str_contains($body,'Cena na upit') && !str_contains($body,'Tehničke specifikacije')); });
    $test('Canonical, robots and SEO overrides are correct', static function () use ($assert,$router,$slug):void { $clean=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug))->body; $query=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug,query:['utm_source'=>'test']))->body; $assert(str_contains($clean,'<title>SEO naslov</title>') && str_contains($clean,'content="SEO opis"') && str_contains($clean,'href="https://example.test/klima-uredjaji/'.$slug.'"') && str_contains($clean,'content="index, follow"') && str_contains($query,'content="noindex, follow"')); });
    $test('SEO fallbacks are deterministic for missing overrides', static function () use ($assert,$router,$noOptionsSlug):void { $body=$router->dispatch(new Request('GET','/klima-uredjaji/'.$noOptionsSlug))->body; $assert(str_contains($body,'No optional data klima | Frigo Sistem Niš') && str_contains($body,'Pogledajte detalje')); });
    $test('Product and breadcrumb schema contain only stored valid data', static function () use ($assert,$router,$slug):void { $body=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug))->body; $assert(str_contains($body,'"@type":"Product"') && str_contains($body,'"sku":"MODEL-') && str_contains($body,'"@type":"BreadcrumbList"') && !str_contains($body,'aggregateRating') && !str_contains($body,'availability') && !str_contains($body,'"offers"')); });
    $test('Product CTA links to the Phase 10 contact workflow', static function () use ($assert,$router,$slug):void { $body=$router->dispatch(new Request('GET','/klima-uredjaji/'.$slug))->body; $assert(str_contains($body,'id="informacije"') && str_contains($body,'href="/kontakt?product='.$slug.'"') && str_contains($body,'Pošaljite upit')); });
    $test('Product view uses Phase 6 deduplication', static function () use ($assert,$router,$slug,$pdo,$productId):void { $before=(int)$pdo->query('SELECT COUNT(*) FROM product_views WHERE product_id='.$productId)->fetchColumn(); $router->dispatch(new Request('GET','/klima-uredjaji/'.$slug)); $router->dispatch(new Request('GET','/klima-uredjaji/'.$slug)); $after=(int)$pdo->query('SELECT COUNT(*) FROM product_views WHERE product_id='.$productId)->fetchColumn(); $assert($after-$before<=1); });
    $test('Related products exclude current and inaccessible records', static function () use ($assert,$repository,$productId,$suffix,$relatedCategoryId):void { $items=$repository->related($productId,'phase8-category-'.$suffix,'phase8-brand-'.$suffix); $ids=array_map('intval',array_column($items,'id')); $assert(in_array($relatedCategoryId,$ids,true) && !in_array($productId,$ids,true)); foreach ($items as $item) $assert(!str_contains($item['slug'],'hidden') && !str_contains($item['slug'],'bad-')); });
    $test('Phase 7 cards now expose crawlable detail links', static function () use ($assert,$pdo,$config,$basePath,$slug):void { $catalog=new PublicCatalogController(new PublicCatalogRepository($pdo),new View($basePath.'/resources/views'),$config); $body=$catalog->index(new Request('GET','/klima-uredjaji',query:['q'=>'MODEL-']))->body; $assert(str_contains($body,'/klima-uredjaji/'.$slug) && !str_contains($body,'Detaljnije — uskoro')); });
    $test('Analytics recording failure never breaks the product page', static function () use ($assert,$repository,$config,$basePath,$slug):void { $isolatedPdo=(new Database($config))->connect(); $failureController=new PublicProductController($repository,new ProductViewService(new AnalyticsRepository($isolatedPdo),str_repeat('phase8-key-',4)),new ProductSeoService(),new View($basePath.'/resources/views'),$config,new Logger($basePath.'/storage/logs/phase8-test.log')); $response=$failureController->show(new Request('GET','/klima-uredjaji/'.$slug,attributes:['slug'=>$slug])); $assert($response->status===200 && str_contains($response->body,'Klima')); });
    $test('Detail repository uses four bounded queries without per-record loops', static function () use ($assert,$basePath):void { $source=file_get_contents($basePath.'/app/Repositories/PublicProductRepository.php'); $assert(substr_count((string)$source,'prepare(')===4 && !str_contains((string)$source,'foreach')); });
} finally { if ($pdo->inTransaction()) $pdo->rollBack(); }
if ($failures!==[]) { fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL); exit(1); }
echo "All Phase 8 product detail checks passed.\n";
