<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Environment;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void {
    try {
        $assertion();
        echo "PASS: {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failures[] = $name . ': ' . $exception->getMessage();
        echo "FAIL: {$name}" . PHP_EOL;
    }
};
$assert = static function (bool $condition, string $message = 'Assertion failed.'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$test('PSR-4 autoloading', static function () use ($assert): void {
    $assert(class_exists(Router::class));
});

$test('Configuration dot lookup', static function () use ($assert): void {
    $config = new Config(['app' => ['name' => 'Frigo Sistem']]);
    $assert($config->get('app.name') === 'Frigo Sistem');
    $assert($config->get('missing', 'fallback') === 'fallback');
});

$test('Environment defaults', static function () use ($assert): void {
    $assert(Environment::get('FRIGO_TEST_MISSING', 'safe') === 'safe');
});

$test('Router dispatch and not found', static function () use ($assert): void {
    $router = new Router();
    $router->get('/', static fn (): Response => Response::html('booted'));
    $assert($router->dispatch(new Request('GET', '/'))->body === 'booted');
    $assert($router->dispatch(new Request('GET', '/missing'))->status === 404);
});

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'All foundation checks passed.' . PHP_EOL;
