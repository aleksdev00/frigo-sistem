<?php

declare(strict_types=1);

use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\PublicCatalogController;
use App\Controllers\PublicProductController;
use App\Controllers\SeoInfrastructureController;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Foundation\Logger;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\AnalyticsRepository;
use App\Repositories\PublicCatalogRepository;
use App\Repositories\PublicProductRepository;
use App\Security\Csrf;
use App\Services\ContactAntiSpam;
use App\Services\ContactRateLimiter;
use App\Services\ContactService;
use App\Services\DevelopmentContactMailer;
use App\Services\LegacyRedirectService;
use App\Services\ProductSeoService;
use App\Services\ProductViewService;
use App\Services\SeoService;
use App\View\View;

$basePath = dirname(__DIR__); require $basePath.'/vendor/autoload.php'; Environment::load($basePath.'/.env');
$config = new Config(['app'=>['name'=>'Frigo Sistem','url'=>'https://seo.example','env'=>'production'],'database'=>require $basePath.'/config/database.php']);
$pdo=(new Database($config))->connect(); $view=new View($basePath.'/resources/views'); $catalogRepo=new PublicCatalogRepository($pdo); $productRepo=new PublicProductRepository($pdo);
$failures=[]; $test=static function(string $name,callable $fn)use(&$failures):void{try{$fn();echo "PASS: $name\n";}catch(Throwable $e){$failures[]="$name: {$e->getMessage()}";echo "FAIL: $name\n";}};
$assert=static function(bool $ok,string $message='Assertion failed.'):void{if(!$ok)throw new RuntimeException($message);};
$pdo->beginTransaction();
try {
    $suffix=bin2hex(random_bytes(4)); $brandSlug='seo-brand-'.$suffix; $categorySlug='seo-category-'.$suffix; $productSlug='seo-product-'.$suffix;
    $pdo->prepare('INSERT INTO brands(name,slug,seo_title,seo_description,is_active,created_at,updated_at) VALUES(?,?,?,?,1,NOW(),NOW())')->execute(['SEO Brand',$brandSlug,null,null]); $brandId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO categories(name,slug,description,seo_title,seo_description,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())')->execute(['SEO Category',$categorySlug,'Stvarni opis',null,null]); $categoryId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO products(brand_id,category_id,name,slug,code,short_description,seo_title,seo_description,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,1,NOW(),NOW())')->execute([$brandId,$categoryId,'SEO Klima',$productSlug,'SKU-SEO','Stvarni kratak opis','Developer naslov','Developer opis']); $productId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO product_images(product_id,image_path,alt_text,is_main,sort_order,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$productId,'uploads/products/seo.webp','SEO Klima uređaj',1,0]);
    $pdo->prepare('INSERT INTO products(brand_id,category_id,name,slug,is_active,created_at,updated_at) VALUES(?,?,?,?,0,NOW(),NOW())')->execute([$brandId,$categoryId,'Hidden SEO','hidden-seo-'.$suffix]);
    $home=new HomeController($catalogRepo,$view,$config); $catalog=new PublicCatalogController($catalogRepo,$view,$config); $seo=new SeoService($config);
    $product=new PublicProductController($productRepo,new ProductViewService(new AnalyticsRepository($pdo),str_repeat('x',32)),new ProductSeoService(),$view,$config,new Logger($basePath.'/storage/logs/phase11-test.log'));
    $infra=new SeoInfrastructureController($catalogRepo,$seo);

    $test('Homepage metadata, H1, OG and Organization schema',static function()use($assert,$home){$b=$home->index(new Request('GET','/'))->body;$assert(str_contains($b,'Klima uređaji Niš')&&str_contains($b,'rel="canonical" href="https://seo.example/"')&&str_contains($b,'content="index, follow"')&&substr_count($b,'<h1')===1&&str_contains($b,'"@type":"Organization"')&&str_contains($b,'og:site_name'));});
    $test('Catalog clean page indexes and filtered page does not',static function()use($assert,$catalog){$clean=$catalog->index(new Request('GET','/klima-uredjaji'))->body;$filtered=$catalog->index(new Request('GET','/klima-uredjaji',query:['q'=>'seo']))->body;$assert(str_contains($clean,'Klima uređaji Niš')&&str_contains($clean,'content="index, follow"')&&str_contains($filtered,'content="noindex, follow"')&&str_contains($filtered,'href="https://seo.example/klima-uredjaji"'));});
    $test('Brand and category metadata and inactive behavior',static function()use($assert,$catalog,$brandSlug,$categorySlug){$brand=$catalog->brand(new Request('GET','/brend/'.$brandSlug,attributes:['slug'=>$brandSlug]));$category=$catalog->category(new Request('GET','/kategorija/'.$categorySlug,attributes:['slug'=>$categorySlug]));$missing=$catalog->brand(new Request('GET','/brend/missing',attributes:['slug'=>'missing']));$assert(str_contains($brand->body,'SEO Brand klima uređaji')&&str_contains($category->body,'SEO Category klima uređaji')&&str_contains($brand->body,'https://seo.example/brend/'.$brandSlug)&&$missing->status===404&&str_contains($missing->body,'noindex, follow'));});
    $test('Product overrides, canonical, OG image and conservative JSON-LD',static function()use($assert,$product,$productSlug,$suffix){$r=$product->show(new Request('GET','/klima-uredjaji/'.$productSlug,attributes:['slug'=>$productSlug]));$hidden=$product->show(new Request('GET','/klima-uredjaji/hidden-seo-'.$suffix,attributes:['slug'=>'hidden-seo-'.$suffix]));$assert(str_contains($r->body,'<title>Developer naslov</title>')&&str_contains($r->body,'https://seo.example/klima-uredjaji/'.$productSlug)&&str_contains($r->body,'https://seo.example/uploads/products/seo.webp')&&str_contains($r->body,'"@type":"Product"')&&!str_contains($r->body,'"offers"')&&!str_contains($r->body,'aggregateRating')&&$hidden->status===404);});
    $test('Sitemap XML contains only public clean URLs',static function()use($assert,$infra,$brandSlug,$categorySlug,$productSlug,$suffix){$r=$infra->sitemap(new Request('GET','/sitemap.xml'));$assert($r->headers['Content-Type']==='application/xml; charset=UTF-8'&&str_contains($r->body,'<urlset')&&str_contains($r->body,'/brend/'.$brandSlug)&&str_contains($r->body,'/kategorija/'.$categorySlug)&&str_contains($r->body,'/klima-uredjaji/'.$productSlug)&&!str_contains($r->body,'hidden-seo-'.$suffix)&&!str_contains($r->body,'/admin')&&!str_contains($r->body,'?q=')&&!str_contains($r->body,'?product='));});
    $test('Production and development robots are safe',static function()use($assert,$infra,$catalogRepo){$prod=$infra->robots(new Request('GET','/robots.txt'));$dev=new SeoInfrastructureController($catalogRepo,new SeoService(new Config(['app'=>['name'=>'Frigo Sistem','url'=>'http://localhost:8000','env'=>'local']])));$local=$dev->robots(new Request('GET','/robots.txt'));$assert($prod->headers['Content-Type']==='text/plain; charset=UTF-8'&&str_contains($prod->body,'Disallow: /admin')&&str_contains($prod->body,'Sitemap: https://seo.example/sitemap.xml')&&trim($local->body)==="User-agent: *\nDisallow: /");});
    $test('APP_URL defeats hostile Host and local metadata is noindex',static function()use($assert,$seo){$_SERVER['HTTP_HOST']='evil.example';$assert($seo->url('/kontakt')==='https://seo.example/kontakt');$local=new SeoService(new Config(['app'=>['url'=>'http://localhost:8000','env'=>'testing']]));$assert($local->page('/','T','D')['robots']==='noindex, nofollow');});
    $test('Redirect map is exact, permanent and rejects external targets',static function()use($assert){$router=new Router();$router->useLegacyRedirects(new LegacyRedirectService(['/verified-old'=>'/kontakt']));$router->get('/verified-old',static fn():Response=>Response::html('wrong'));$r=$router->dispatch(new Request('GET','/verified-old',query:['utm'=>'x']));$assert($r->status===301&&$r->headers['Location']==='/kontakt'&&$router->dispatch(new Request('GET','/unknown'))->status===404);try{new LegacyRedirectService(['/old'=>'https://evil.example']);throw new RuntimeException('External target accepted.');}catch(InvalidArgumentException){}});
    $test('Unknown route is a real noindex 404',static function()use($assert){$r=(new Router())->dispatch(new Request('GET','/does-not-exist'));$assert($r->status===404&&str_contains($r->body,'noindex, follow')&&str_contains($r->body,'href="/klima-uredjaji"'));});
} finally { if($pdo->inTransaction())$pdo->rollBack(); }
if($failures!==[]){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);} echo "All Phase 11 SEO checks passed.\n";
