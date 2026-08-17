<?php

declare(strict_types=1);

use App\Controllers\ContactController;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Foundation\Logger;
use App\Http\Request;
use App\Repositories\PublicProductRepository;
use App\Security\Csrf;
use App\Services\ContactAntiSpam;
use App\Services\ContactMailer;
use App\Services\ContactRateLimiter;
use App\Services\ContactService;
use App\View\View;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');
$config = new Config(['app'=>['name'=>'Frigo Sistem','url'=>'https://example.test'],'database'=>require $basePath.'/config/database.php']);
$pdo = (new Database($config))->connect();
if (session_status() !== PHP_SESSION_ACTIVE) { session_id('phase10-' . bin2hex(random_bytes(8))); session_start(); }
$failures=[];
$test=static function(string $name,callable $assertion)use(&$failures):void{try{$assertion();echo "PASS: {$name}\n";}catch(Throwable $e){$failures[]=$name.': '.$e->getMessage();echo "FAIL: {$name}\n";}};
$assert=static function(bool $condition,string $message='Assertion failed.'):void{if(!$condition)throw new RuntimeException($message);};
$temp=sys_get_temp_dir().'/frigo-phase10-'.bin2hex(random_bytes(5)); mkdir($temp,0700,true);
$pdo->beginTransaction();
try {
    $suffix=bin2hex(random_bytes(4));
    $pdo->prepare('INSERT INTO brands (name,slug,is_active,created_at,updated_at) VALUES (?,?,1,NOW(),NOW())')->execute(['Phase 10 brand','phase10-brand-'.$suffix]); $brandId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO categories (name,slug,is_active,created_at,updated_at) VALUES (?,?,1,NOW(),NOW())')->execute(['Phase 10 category','phase10-category-'.$suffix]); $categoryId=(int)$pdo->lastInsertId();
    $insert=$pdo->prepare('INSERT INTO products (brand_id,category_id,name,slug,code,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
    $slug='phase10-product-'.$suffix; $insert->execute([$brandId,$categoryId,'Midea <Test>',$slug,'X-12',1]);
    $hidden='phase10-hidden-'.$suffix; $insert->execute([$brandId,$categoryId,'Hidden product',$hidden,'H-1',0]);
    $repository=new PublicProductRepository($pdo); $view=new View($basePath.'/resources/views'); $csrf=new Csrf(); $_SESSION['_csrf_token']=str_repeat('c',64);
    $antiSpam=new ContactAntiSpam(str_repeat('phase10-secret-',3),3);
    $mail=new class implements ContactMailer { public array $sent=[]; public function send(array $inquiry):void{$this->sent[]=$inquiry;} };
    $controller=new ContactController($repository,new ContactService($mail),$antiSpam,new ContactRateLimiter($temp.'/rate-main',str_repeat('rate-secret-',4),20,3600),$csrf,$view,$config,new Logger($temp.'/test.log'));
    $valid=static function(?string $product=null)use($antiSpam):array{$_SESSION['_csrf_token']=str_repeat('c',64);$timing=$antiSpam->fields(time()-5);return ['_csrf'=>str_repeat('c',64),'_form_started'=>$timing['started'],'_form_signature'=>$timing['signature'],'website'=>'','name'=>'Petar Petrović','email'=>'petar@example.com','phone'=>'','message'=>'Želim više informacija o ponudi.']+($product===null?[]:['product'=>$product]);};

    $test('GET /kontakt renders accessible metadata and canonical',static function()use($assert,$controller):void{$r=$controller->show(new Request('GET','/kontakt'));$assert($r->status===200&&str_contains($r->body,'<h1>Kontaktirajte nas</h1>')&&str_contains($r->body,'<label for="name">')&&str_contains($r->body,'rel="canonical" href="https://example.test/kontakt"')&&str_contains($r->body,'content="index, follow"'));});
    $test('Valid general inquiry invokes mail service and uses PRG',static function()use($assert,$controller,$valid,$mail):void{$r=$controller->submit(new Request('POST','/kontakt',$valid(), '203.0.113.1'));$assert($r->status===303&&$r->headers['Location']==='/kontakt?sent=1'&&count($mail->sent)===1&&$mail->sent[0]['product']===null);});
    $test('Valid product inquiry uses server-resolved active product data',static function()use($assert,$controller,$valid,$mail,$slug):void{$_SESSION['_csrf_token']=str_repeat('c',64);$r=$controller->submit(new Request('POST','/kontakt',$valid($slug),'203.0.113.2'));$last=$mail->sent[array_key_last($mail->sent)];$assert($r->status===303&&$last['product']['name']==='Midea <Test>'&&$last['product']['code']==='X-12'&&$last['product']['slug']===$slug);});
    $test('Hidden and invalid product references are ignored safely',static function()use($assert,$controller,$valid,$mail,$hidden):void{foreach([$hidden,'../bad','unknown-product']as$reference){$_SESSION['_csrf_token']=str_repeat('c',64);$before=count($mail->sent);$r=$controller->submit(new Request('POST','/kontakt',$valid($reference),'203.0.113.'.(10+$before)));$assert($r->status===303&&$mail->sent[array_key_last($mail->sent)]['product']===null);}});
    $service=new ContactService($mail);
    $test('Validation requires name and one contact method',static function()use($assert,$service):void{$r=$service->validate(['name'=>'','email'=>'','phone'=>'','message'=>'Dovoljno duga poruka']);$assert(isset($r->errors['name'],$r->errors['email'],$r->errors['phone']));});
    $test('Email is validated while phone remains optional',static function()use($assert,$service):void{$bad=$service->validate(['name'=>'Ime','email'=>'bad-email','phone'=>'','message'=>'Dovoljno duga poruka']);$phone=$service->validate(['name'=>'Ime','email'=>'','phone'=>'+381 (60) 123-45-67','message'=>'Dovoljno duga poruka']);$assert(isset($bad->errors['email'])&&$phone->isValid());});
    $test('Empty, short and oversized messages and values are bounded',static function()use($assert,$service):void{foreach(['','kratko',str_repeat('a',5001)]as$message)$assert(isset($service->validate(['name'=>'Ime','email'=>'a@b.rs','phone'=>'','message'=>$message])->errors['message']));$assert(isset($service->validate(['name'=>str_repeat('a',121),'email'=>'a@b.rs','phone'=>'','message'=>'Dovoljno duga poruka'])->errors['name']));});
    $test('Validation errors preserve and escape entered values',static function()use($assert,$controller,$valid):void{$input=$valid();$input['name']='<script>alert(1)</script>'; $input['email']='bad';$r=$controller->submit(new Request('POST','/kontakt',$input,'203.0.113.30'));$assert($r->status===422&&str_contains($r->body,'value="&lt;script&gt;alert(1)&lt;/script&gt;"')&&!str_contains($r->body,'<script>alert(1)</script>')&&str_contains($r->body,'aria-describedby="email-error"'));});
    $test('Missing CSRF is rejected',static function()use($assert,$controller,$valid):void{$input=$valid();unset($input['_csrf']);$assert($controller->submit(new Request('POST','/kontakt',$input,'203.0.113.31'))->status===419);});
    $test('Honeypot and minimum timing reject automated submissions',static function()use($assert,$controller,$valid,$antiSpam):void{$honey=$valid();$honey['website']='spam';$assert($controller->submit(new Request('POST','/kontakt',$honey,'203.0.113.32'))->status===422);$fast=$valid();$timing=$antiSpam->fields();$fast['_form_started']=$timing['started'];$fast['_form_signature']=$timing['signature'];$assert($controller->submit(new Request('POST','/kontakt',$fast,'203.0.113.33'))->status===422);});
    $test('Rate limiter stores only hashed identifiers and enforces bounds',static function()use($assert,$temp):void{$dir=$temp.'/rate-unit';$limiter=new ContactRateLimiter($dir,str_repeat('secret-',6),2,60);$assert($limiter->consume('198.51.100.2',100)&&$limiter->consume('198.51.100.2',101)&&!$limiter->consume('198.51.100.2',102));$files=glob($dir.'/*.json');$assert(count($files)===1&&!str_contains((string)$files[0],'198.51.100.2')&&!str_contains((string)file_get_contents($files[0]),'198.51.100.2'));});
    $test('Header injection email is rejected before mail invocation',static function()use($assert,$service):void{$r=$service->validate(['name'=>'Ime','email'=>"a@b.rs\r\nBcc:x@y.rs",'phone'=>'','message'=>'Dovoljno duga poruka']);$assert(isset($r->errors['email']));});
    $test('Delivery failure is generic and does not claim success',static function()use($assert,$repository,$antiSpam,$csrf,$view,$config,$temp,$valid):void{$failing=new class implements ContactMailer{public function send(array $inquiry):void{throw new RuntimeException('smtp.internal.local secret');}};$c=new ContactController($repository,new ContactService($failing),$antiSpam,new ContactRateLimiter($temp.'/rate-fail',str_repeat('fail-secret-',3),5,3600),$csrf,$view,$config,new Logger($temp.'/failure.log'));$_SESSION['_csrf_token']=str_repeat('c',64);$r=$c->submit(new Request('POST','/kontakt',$valid(),'203.0.113.40'));$assert($r->status===503&&str_contains($r->body,'Došlo je do problema')&&!str_contains($r->body,'smtp.internal.local')&&!str_contains($r->body,'uspešno poslata'));});
    $test('Success state does not resend and query variants are noindex',static function()use($assert,$controller,$mail):void{$before=count($mail->sent);$r=$controller->show(new Request('GET','/kontakt',query:['sent'=>'1']));$assert($r->status===200&&count($mail->sent)===$before&&str_contains($r->body,'Poruka je uspešno poslata')&&str_contains($r->body,'content="noindex, follow"'));});
    $test('Product context page escapes trusted database output',static function()use($assert,$controller,$slug):void{$r=$controller->show(new Request('GET','/kontakt',query:['product'=>$slug]));$assert(str_contains($r->body,'Midea &lt;Test&gt;')&&!str_contains($r->body,'Midea <Test>')&&str_contains($r->body,'value="'.$slug.'"'));});
} finally {
    if($pdo->inTransaction())$pdo->rollBack();
    if(is_dir($temp)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as$item){$item->isDir()?rmdir($item->getPathname()):unlink($item->getPathname());}rmdir($temp);}
}
if($failures!==[]){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "All Phase 10 contact checks passed.\n";
