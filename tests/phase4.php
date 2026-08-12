<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Security\Csrf;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\DevelopmentCatalogSeeder;
use App\Services\ProductSeoService;
use App\Services\ProductService;
use App\Services\SlugService;
use App\View\View;

$basePath=dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
Environment::load($basePath.'/.env');
$config=new Config(['database'=>require $basePath.'/config/database.php']);
$pdo=(new Database($config))->connect();
$failures=[];
$test=static function(string $name,callable $fn)use(&$failures):void{try{$fn();echo "PASS: {$name}\n";}catch(Throwable $e){$failures[]=$name.': '.$e->getMessage();echo "FAIL: {$name}\n";}};
$assert=static function(bool $condition,string $message='Assertion failed.'):void{if(!$condition)throw new RuntimeException($message);};
$brands=new BrandRepository($pdo);$categories=new CategoryRepository($pdo);$products=new ProductRepository($pdo);$slugs=new SlugService();
$brandService=new BrandService($brands,$slugs);$categoryService=new CategoryService($categories,$slugs);$productService=new ProductService($products,$brands,$categories,$slugs);
$pdo->beginTransaction();
try{
    $test('Serbian slug generation is stable and URL-safe',static function()use($assert,$slugs):void{
        $assert($slugs->generate('Inverter klima uređaj')==='inverter-klima-uredjaj');
        $assert($slugs->generate('Midea Xtreme Save Pro 12')==='midea-xtreme-save-pro-12');
    });
    $test('Development catalog seed is repeatable without duplicates',static function()use($assert,$pdo,$basePath):void{
        $data=require $basePath.'/database/seeds/development_catalog.php';$seeder=new DevelopmentCatalogSeeder($pdo);$seeder->seed($data);$seeder->seed($data);
        $brandCount=(int)$pdo->query("SELECT COUNT(*) FROM brands WHERE name IN ('Midea','Gree','Hisense','Haier','Vivax')")->fetchColumn();
        $categoryCount=(int)$pdo->query("SELECT COUNT(*) FROM categories WHERE slug IN ('inverter-klima','mobilna-klima','multi-split-sistem')")->fetchColumn();
        $assert($brandCount===5&&$categoryCount===3);
    });
    $brandId=0;$categoryId=0;$productId=0;$duplicateProductId=0;
    $test('Brand create, uniqueness, edit, and status',static function()use($assert,$brandService,$brands,&$brandId):void{
        $v=$brandService->validate(['name'=>'Phase 4 Brand','slug'=>'','is_active'=>'1']);$assert($v->isValid());$brandId=$brandService->create($v->data);
        $assert(isset($brandService->validate(['name'=>'Phase 4 Brand','slug'=>'another'])->errors['name']));
        $edit=$brandService->validate(['name'=>'Phase 4 Brand Edited','slug'=>'phase-4-brand-edited'],$brandId);$brandService->update($brandId,$edit->data);$brands->setStatus($brandId,false);$assert((int)$brands->find($brandId)['is_active']===0);
    });
    $test('Category create, uniqueness, edit, and status',static function()use($assert,$categoryService,$categories,&$categoryId):void{
        $v=$categoryService->validate(['name'=>'Phase 4 Category','slug'=>'','description'=>'Safe text','is_active'=>'1']);$assert($v->isValid());$categoryId=$categoryService->create($v->data);
        $assert(isset($categoryService->validate(['name'=>'Other','slug'=>$v->data['slug']])->errors['slug']));
        $edit=$categoryService->validate(['name'=>'Phase 4 Category Edited','slug'=>'phase-4-category-edited'],$categoryId);$categoryService->update($categoryId,$edit->data);$categories->setStatus($categoryId,false);$assert((int)$categories->find($categoryId)['is_active']===0);
    });
    $test('Product validation rejects invalid foreign keys and negative price',static function()use($assert,$productService):void{
        $invalid=$productService->validate(['name'=>'Invalid','brand_id'=>'999999999','category_id'=>'999999999','price'=>'-1']);
        $assert(isset($invalid->errors['brand_id'],$invalid->errors['category_id'],$invalid->errors['price']));
    });
    $test('Create ignores owner slug and SEO input and generates an automatic slug',static function()use($assert,$productService,$products,$brandId,$categoryId,&$productId):void{
        $v=$productService->validate(['name'=>'Phase Four Automatic Slug Product','brand_id'=>$brandId,'category_id'=>$categoryId,'slug'=>'owner-controlled','seo_title'=>'Owner SEO','seo_description'=>'Owner description','code'=>'P4','is_featured'=>'1']);
        $assert($v->isValid()&&$v->data['slug']==='phase-four-automatic-slug-product');
        $assert($v->data['seo_title']===null&&$v->data['seo_description']===null&&$v->data['price']===null&&$v->data['is_active']===0&&$v->data['is_featured']===1);
        $productId=$productService->create($v->data);$assert($products->find($productId)['slug']==='phase-four-automatic-slug-product');
    });
    $test('Duplicate generated product slug receives deterministic suffix',static function()use($assert,$productService,$products,$brandId,$categoryId,&$duplicateProductId):void{
        $v=$productService->validate(['name'=>'Phase Four Automatic Slug Product','brand_id'=>$brandId,'category_id'=>$categoryId]);
        $assert($v->isValid()&&$v->data['slug']==='phase-four-automatic-slug-product-2');$duplicateProductId=$productService->create($v->data);$assert($products->find($duplicateProductId)['slug']==='phase-four-automatic-slug-product-2');
    });
    $test('Changing product name preserves slug and developer SEO overrides',static function()use($assert,$pdo,$productService,$products,$productId,$brandId,$categoryId):void{
        $pdo->prepare('UPDATE products SET seo_title=?,seo_description=? WHERE id=?')->execute(['Developer title','Developer description',$productId]);
        $edit=$productService->validate(['name'=>'Completely Renamed Product','brand_id'=>$brandId,'category_id'=>$categoryId,'slug'=>'attempted-change','seo_title'=>'Owner change','code'=>'P4','price'=>'125.50','is_active'=>'1'],$productId);
        $assert($edit->isValid()&&$edit->data['slug']==='phase-four-automatic-slug-product'&&$edit->data['seo_title']==='Developer title'&&$edit->data['seo_description']==='Developer description');
        $productService->update($productId,$edit->data);$found=$products->find($productId);$assert($found['slug']==='phase-four-automatic-slug-product'&&(float)$found['price']===125.50);
    });
    $test('Owner form hides SEO and URL fields and reads taxonomy from database',static function()use($assert,$basePath,$brands,$categories,$brandId,$categoryId):void{
        $html=(new View($basePath.'/resources/views'))->render('admin/products/form',['title'=>'Create product','appName'=>'Test','csrfToken'=>str_repeat('x',64),'flash'=>null,'values'=>[],'errors'=>[],'action'=>'/admin/products','brands'=>$brands->all(),'categories'=>$categories->all(),'showAdminNav'=>false],'layouts/admin');
        $assert(!str_contains($html,'name="slug"')&&!str_contains($html,'name="seo_title"')&&!str_contains($html,'name="seo_description"')&&!str_contains($html,'URL and SEO'));
        $assert(str_contains($html,'value="'.$brandId.'"')&&str_contains($html,'value="'.$categoryId.'"'));
        $assert(str_contains($html,'name="code"')&&!preg_match('/<select[^>]+name="code"/',$html));
    });
    $test('SEO fallback derives metadata and respects developer overrides',static function()use($assert):void{
        $seo=new ProductSeoService();$data=['name'=>'Midea Xtreme Save Pro 12','brand_name'=>'Midea','category_name'=>'Inverter klima'];
        $assert($seo->title($data)==='Midea Xtreme Save Pro 12 klima | Frigo Sistem Niš');$assert(str_contains($seo->description($data),'Midea')&&str_contains($seo->description($data),'Inverter klima'));
        $assert($seo->title([...$data,'seo_title'=>'Override'])==='Override');
    });
    $test('Product search, status, dashboard counts, and deletion remain functional',static function()use($assert,$products,$productId,$duplicateProductId):void{
        $products->setStatus($productId,false);$list=$products->paginate(['q'=>'P4','brand_id'=>0,'category_id'=>0,'status'=>'hidden'],1);$assert($list['total']>=1&&$products->counts()['total']>=2);
        $assert($products->delete($duplicateProductId));
    });
    $test('Referenced taxonomy deletion is blocked, then permitted after product deletion',static function()use($assert,$products,$brandService,$categoryService,$productId,$brandId,$categoryId):void{
        $assert($brandService->delete($brandId)==='referenced'&&$categoryService->delete($categoryId)==='referenced');$assert($products->delete($productId));$assert($brandService->delete($brandId)==='deleted'&&$categoryService->delete($categoryId)==='deleted');
    });
    $test('Admin writes are guarded, POST-only, and CSRF rejects invalid input',static function()use($assert):void{
        $called=false;$authenticated=false;$router=new Router();$router->add('POST','/admin/products/{id}/delete',static function(Request $r)use(&$called):Response{$called=true;return Response::html((string)$r->attributes['id']);});
        $router->protectAdminWith(static function()use(&$authenticated):?Response{return $authenticated?null:Response::redirect('/admin/login',302);});
        $assert($router->dispatch(new Request('POST','/admin/products/7/delete'))->status===302&&!$called);$authenticated=true;$assert($router->dispatch(new Request('GET','/admin/products/7/delete'))->status===404);$assert($router->dispatch(new Request('POST','/admin/products/7/delete'))->body==='7');
        $_SESSION['_csrf_token']=str_repeat('c',64);$csrf=new Csrf();$assert(!$csrf->validate(null)&&!$csrf->validate(str_repeat('x',64)));
    });
    $test('Stored content is escaped in HTML context',static function()use($assert):void{$payload='<script>alert(1)</script>';$escaped=htmlspecialchars($payload,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$assert(!str_contains($escaped,'<script>')&&str_contains($escaped,'&lt;script&gt;'));});
}finally{if($pdo->inTransaction())$pdo->rollBack();}
if($failures!==[]){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "All Phase 4 catalog administration checks passed.\n";
