<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Foundation\Application;
use App\Foundation\Config;
use App\Foundation\Environment;
use App\Foundation\ErrorHandler;
use App\Foundation\Logger;
use App\Http\Router;
use App\View\View;

$basePath = dirname(__DIR__);
$autoload = $basePath . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException('Composer dependencies are missing. Run "composer install".');
}

require $autoload;

Environment::load($basePath . '/.env');

$config = new Config([
    'app' => require $basePath . '/config/app.php',
    'database' => require $basePath . '/config/database.php',
]);

date_default_timezone_set((string) $config->get('app.timezone', 'Europe/Belgrade'));

$logger = new Logger($basePath . '/storage/logs/app.log');
$errors = new ErrorHandler(
    logger: $logger,
    debug: (bool) $config->get('app.debug', false),
    viewPath: $basePath . '/resources/views',
);
$errors->register();

$views = new View($basePath . '/resources/views');
$router = new Router();
$home = new HomeController($views, $config);

$router->get('/', [$home, 'index']);

return new Application($router, $errors);
