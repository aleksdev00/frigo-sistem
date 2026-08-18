<?php

declare(strict_types=1);

use App\Foundation\Application;
use App\Foundation\Config;
use App\Foundation\ErrorHandler;
use App\Foundation\Logger;
use App\Foundation\ProductionConfiguration;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void {
    try { $assertion(); echo "PASS: {$name}\n"; }
    catch (Throwable $exception) { $failures[] = $name . ': ' . $exception->getMessage(); echo "FAIL: {$name}\n"; }
};
$assert = static function (bool $condition, string $message = 'Assertion failed.'): void {
    if (!$condition) throw new RuntimeException($message);
};
$expectFailure = static function (callable $action) use ($assert): void {
    try { $action(); } catch (RuntimeException) { return; }
    $assert(false, 'Unsafe production configuration was accepted.');
};

$validProduction = [
    'app' => ['env'=>'production','debug'=>false,'url'=>'https://example.com','key'=>str_repeat('k',32),'session_idle_timeout'=>1800],
    'database' => ['host'=>'db','database'=>'frigo','username'=>'frigo','password'=>'secret'],
    'mail' => ['transport'=>'smtp','host'=>'smtp.example.com','username'=>'site@example.com','password'=>'secret','from_address'=>'site@example.com','from_name'=>'Frigo Sistem','to_address'=>'office@example.com'],
];

$test('Production rejects debug mode, HTTP canonical URL, and missing secrets', static function () use ($expectFailure, $validProduction): void {
    $expectFailure(static fn () => ProductionConfiguration::validate(new Config([...$validProduction, 'app'=>[...$validProduction['app'],'debug'=>true]])));
    $expectFailure(static fn () => ProductionConfiguration::validate(new Config([...$validProduction, 'app'=>[...$validProduction['app'],'url'=>'http://example.com']])));
    $expectFailure(static fn () => ProductionConfiguration::validate(new Config([...$validProduction, 'database'=>[...$validProduction['database'],'password'=>'']])));
    $expectFailure(static fn () => ProductionConfiguration::validate(new Config([...$validProduction, 'mail'=>[...$validProduction['mail'],'password'=>'']])));
});

$test('Local development is not burdened by production requirements', static function () use ($assert): void {
    ProductionConfiguration::validate(new Config(['app'=>['env'=>'local','debug'=>true,'url'=>'http://localhost']]));
    $assert(true);
});

$test('HSTS is emitted only for secure production requests', static function () use ($assert): void {
    $router = new Router();
    $router->get('/', static fn (): Response => Response::html('ok'));
    $errors = new ErrorHandler(new Logger(sys_get_temp_dir() . '/frigo-phase12.log'), false, dirname(__DIR__) . '/resources/views');
    $production = new Application($router, $errors, true);
    $assert(!isset($production->handle(new Request('GET','/'))->effectiveHeaders()['Strict-Transport-Security']));
    $assert(isset($production->handle(new Request('GET','/', secure:true))->effectiveHeaders()['Strict-Transport-Security']));
    $local = new Application($router, $errors, false);
    $assert(!isset($local->handle(new Request('GET','/', secure:true))->effectiveHeaders()['Strict-Transport-Security']));
});

$test('Security policy blocks framing and unsafe cross-origin defaults', static function () use ($assert): void {
    $headers = Response::html('ok')->effectiveHeaders();
    $assert($headers['X-Frame-Options'] === 'DENY');
    $assert(str_contains($headers['Content-Security-Policy'], "frame-ancestors 'none'"));
    $assert(str_contains($headers['Content-Security-Policy'], "form-action 'self'"));
});

$test('Redirect helper rejects external and protocol-relative destinations', static function () use ($assert): void {
    foreach (['https://evil.example', '//evil.example'] as $destination) {
        try { Response::redirect($destination); throw new RuntimeException('Unsafe redirect accepted.'); }
        catch (InvalidArgumentException) { $assert(true); }
    }
});

$test('Unknown and wrong-method write routes cannot mutate state', static function () use ($assert): void {
    $mutated = false; $router = new Router();
    $router->add('POST','/admin/products/{id}/delete',static function () use (&$mutated): Response { $mutated=true; return Response::html('ok'); });
    $assert($router->dispatch(new Request('GET','/admin/products/1/delete'))->status === 404 && !$mutated);
    $assert($router->dispatch(new Request('GET','/definitely-missing'))->status === 404);
});

$test('Request path normalization exposes traversal to strict route matching and rejects null bytes', static function () use ($assert): void {
    $assert(Request::normalizePath('/uploads/%2e%2e/app') === '/uploads/../app');
    $assert(Request::normalizePath('/bad%00path') === '/');
});

$test('Upload hard limits include decoded pixel and batch bounds', static function () use ($assert): void {
    $source = file_get_contents(dirname(__DIR__) . '/app/Services/ProductImageService.php');
    $assert(App\Services\ImageProcessor::MAX_SOURCE_PIXELS <= 40000000);
    $assert(is_string($source) && str_contains($source, 'MAX_FILES_PER_REQUEST'));
});

if ($failures !== []) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "All Phase 12 hardening checks passed.\n";
