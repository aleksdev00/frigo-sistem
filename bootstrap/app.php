<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\AdminAuthController;
use App\Controllers\BrandController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
use App\Foundation\Application;
use App\Foundation\Config;
use App\Foundation\Environment;
use App\Foundation\ErrorHandler;
use App\Foundation\Logger;
use App\Foundation\Database;
use App\Http\Router;
use App\Http\Response;
use App\Repositories\AdminRepository;
use App\Repositories\LoginThrottleRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Security\Csrf;
use App\Security\SessionManager;
use App\Services\AuthService;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SlugService;
use App\Support\AdminPage;
use App\Support\Flash;
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

$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
$sessions = new SessionManager(
    idleTimeout: (int) $config->get('app.session_idle_timeout', 1800),
    secureCookie: $isHttps,
);
$sessions->start();

$appKey = (string) $config->get('app.key', '');
if (strlen($appKey) < 32) {
    if ((string) $config->get('app.env', 'production') === 'production') {
        throw new RuntimeException('APP_KEY must contain at least 32 characters in production.');
    }
    $appKey = hash('sha256', $basePath . '|' . (string) $config->get('database.database'));
}

$views = new View($basePath . '/resources/views');
$router = new Router();
$home = new HomeController($views, $config);
$pdo = (new Database($config))->connect();
$auth = new AuthService(
    new AdminRepository($pdo),
    new LoginThrottleRepository($pdo),
    $sessions,
    $appKey,
);
$csrf = new Csrf();
$admin = new AdminAuthController($views, $config, $auth, $csrf);
$flash = new Flash();
$adminPage = new AdminPage($views, $config, $csrf, $flash);
$brandRepository = new BrandRepository($pdo);
$categoryRepository = new CategoryRepository($pdo);
$productRepository = new ProductRepository($pdo);
$slugService = new SlugService();
$brands = new BrandController($brandRepository, new BrandService($brandRepository, $slugService), $adminPage, $flash);
$categories = new CategoryController($categoryRepository, new CategoryService($categoryRepository, $slugService), $adminPage, $flash);
$products = new ProductController($productRepository, $brandRepository, $categoryRepository, new ProductService($productRepository, $brandRepository, $categoryRepository, $slugService), $adminPage, $flash);
$dashboard = new DashboardController($productRepository, $brandRepository, $categoryRepository, $auth, $adminPage);

$router->get('/', [$home, 'index']);
$router->get('/admin/login', [$admin, 'showLogin']);
$router->add('POST', '/admin/login', [$admin, 'login']);
$router->get('/admin', [$dashboard, 'index']);
$router->add('POST', '/admin/logout', [$admin, 'logout']);
$router->get('/admin/brands', [$brands, 'index']);
$router->get('/admin/brands/create', [$brands, 'create']);
$router->add('POST', '/admin/brands', [$brands, 'store']);
$router->get('/admin/brands/{id}/edit', [$brands, 'edit']);
$router->add('POST', '/admin/brands/{id}', [$brands, 'update']);
$router->add('POST', '/admin/brands/{id}/status', [$brands, 'status']);
$router->add('POST', '/admin/brands/{id}/delete', [$brands, 'delete']);
$router->get('/admin/categories', [$categories, 'index']);
$router->get('/admin/categories/create', [$categories, 'create']);
$router->add('POST', '/admin/categories', [$categories, 'store']);
$router->get('/admin/categories/{id}/edit', [$categories, 'edit']);
$router->add('POST', '/admin/categories/{id}', [$categories, 'update']);
$router->add('POST', '/admin/categories/{id}/status', [$categories, 'status']);
$router->add('POST', '/admin/categories/{id}/delete', [$categories, 'delete']);
$router->get('/admin/products', [$products, 'index']);
$router->get('/admin/products/create', [$products, 'create']);
$router->add('POST', '/admin/products', [$products, 'store']);
$router->get('/admin/products/{id}/edit', [$products, 'edit']);
$router->add('POST', '/admin/products/{id}', [$products, 'update']);
$router->add('POST', '/admin/products/{id}/status', [$products, 'status']);
$router->add('POST', '/admin/products/{id}/delete', [$products, 'delete']);
$router->protectAdminWith(static fn (): ?Response => $auth->isAuthenticated()
    ? null
    : Response::redirect('/admin/login', 302));

return new Application($router, $errors);
