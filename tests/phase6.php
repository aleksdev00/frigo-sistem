<?php

declare(strict_types=1);

use App\Controllers\AnalyticsController;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\AnalyticsRepository;
use App\Security\Csrf;
use App\Services\ProductViewService;
use App\Support\AdminPage;
use App\Support\Flash;
use App\View\View;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');
$config = new Config(['app' => ['name' => 'Frigo Sistem'], 'database' => require $basePath . '/config/database.php']);
$pdo = (new Database($config))->connect();
$failures = [];
$test = static function (string $name, callable $assertion) use (&$failures): void { try { $assertion(); echo "PASS: {$name}\n"; } catch (Throwable $exception) { $failures[] = $name . ': ' . $exception->getMessage(); echo "FAIL: {$name}\n"; } };
$assert = static function (bool $condition, string $message = 'Assertion failed.'): void { if (!$condition) throw new RuntimeException($message); };
$analytics = new AnalyticsRepository($pdo);
$recorder = new ProductViewService($analytics, str_repeat('phase6-key-', 4), 1800);
$pdo->beginTransaction();
try {
    $pdo->exec('DELETE FROM product_views');
    $suffix = bin2hex(random_bytes(4));
    $insertBrand = $pdo->prepare('INSERT INTO brands (name,slug,is_active,created_at,updated_at) VALUES (?,?,1,NOW(),NOW())');
    $insertBrand->execute(['<script>Brand ' . $suffix . '</script>', 'phase6-brand-' . $suffix]);
    $brandId = (int) $pdo->lastInsertId();
    $insertCategory = $pdo->prepare('INSERT INTO categories (name,slug,is_active,created_at,updated_at) VALUES (?,?,1,NOW(),NOW())');
    $insertCategory->execute(['Inverter ' . $suffix, 'phase6-inverter-' . $suffix]);
    $categoryOne = (int) $pdo->lastInsertId();
    $insertCategory->execute(['Mobilna ' . $suffix, 'phase6-mobilna-' . $suffix]);
    $categoryTwo = (int) $pdo->lastInsertId();
    $insertProduct = $pdo->prepare('INSERT INTO products (brand_id,category_id,name,slug,is_active,created_at,updated_at) VALUES (?,?,?,?,1,NOW(),NOW())');
    $insertProduct->execute([$brandId, $categoryOne, '<img src=x onerror=alert(1)>', 'phase6-one-' . $suffix]);
    $productOne = (int) $pdo->lastInsertId();
    $insertProduct->execute([$brandId, $categoryTwo, 'Phase 6 Product Two', 'phase6-two-' . $suffix]);
    $productTwo = (int) $pdo->lastInsertId();

    $test('Valid product view is recorded without raw IP', static function () use ($assert, $recorder, $pdo, $productOne): void {
        $assert($recorder->recordView($productOne, ['visitor_token' => 'visitor-a', 'ip' => '203.0.113.9'], new DateTimeImmutable('now')));
        $row = $pdo->query('SELECT visitor_token_hash FROM product_views LIMIT 1')->fetch();
        $assert(is_array($row) && strlen($row['visitor_token_hash']) === 64 && !str_contains($row['visitor_token_hash'], '203.0.113.9') && !str_contains($row['visitor_token_hash'], 'visitor-a'));
    });
    $test('Invalid product ID is rejected', static function () use ($assert, $recorder): void {
        try { $recorder->recordView(999999999, ['visitor_token' => 'visitor-z']); } catch (InvalidArgumentException) { return; }
        $assert(false, 'Invalid product was accepted.');
    });
    $test('Same visitor and product are reduced during cooldown', static function () use ($assert, $recorder, $analytics, $productOne): void {
        $now = new DateTimeImmutable('now');
        $before = $analytics->summary(null)['total_views'];
        $assert(!$recorder->recordView($productOne, ['visitor_token' => 'visitor-a'], $now->modify('+5 minutes')));
        $assert($recorder->recordView($productOne, ['visitor_token' => 'visitor-a'], $now->modify('+31 minutes')));
        $assert($analytics->summary(null)['total_views'] === $before + 1);
    });

    $pdo->exec('DELETE FROM product_views');
    $insertView = $pdo->prepare('INSERT INTO product_views (product_id,viewed_at,visitor_token_hash) VALUES (?,?,?)');
    foreach ([[$productOne, 'today', 'a'], [$productOne, '-2 days', 'b'], [$productOne, '-15 days', 'c'], [$productTwo, '-2 days', 'd'], [$productTwo, '-60 days', 'e']] as [$productId, $relative, $token]) {
        $insertView->execute([$productId, (new DateTimeImmutable($relative))->format('Y-m-d H:i:s'), hash('sha256', $token)]);
    }
    $today = new DateTimeImmutable('today');
    $seven = $today->modify('-6 days');
    $thirty = $today->modify('-29 days');
    $test('Totals and product counts calculate cleanly', static function () use ($assert, $analytics): void { $assert($analytics->summary(null) === ['total_views' => 5, 'viewed_products' => 2]); });
    $test('Top product calculation is ordered by views', static function () use ($assert, $analytics, $productOne): void { $top = $analytics->topProducts(null); $assert((int) $top[0]['id'] === $productOne && (int) $top[0]['views'] === 3); });
    $test('Top category calculation aggregates product categories', static function () use ($assert, $analytics, $categoryOne): void { $top = $analytics->topCategories(null); $assert((int) $top[0]['id'] === $categoryOne && (int) $top[0]['views'] === 3); });
    $test('7-day filtering excludes older views', static function () use ($assert, $analytics, $seven): void { $assert($analytics->summary($seven)['total_views'] === 3); });
    $test('30-day filtering excludes views older than 30 days', static function () use ($assert, $analytics, $thirty): void { $assert($analytics->summary($thirty)['total_views'] === 4); });
    $test('All-time filtering includes every view', static function () use ($assert, $analytics): void { $assert($analytics->summary(null)['total_views'] === 5 && count($analytics->dailyViews(null)) === 4); });
    $test('Empty analytics state uses zeroes and empty lists', static function () use ($assert, $analytics): void { $future = new DateTimeImmutable('+1 day'); $assert($analytics->summary($future) === ['total_views' => 0, 'viewed_products' => 0] && $analytics->topProducts($future) === [] && $analytics->topCategories($future) === [] && $analytics->dailyViews($future) === []); });

    $page = new AdminPage(new View($basePath . '/resources/views'), new Config(['app' => ['name' => 'Frigo Sistem']]), new Csrf(), new Flash());
    $controller = new AnalyticsController($analytics, $page);
    $test('Invalid range falls back safely to 30 days', static function () use ($assert, $controller): void { $response = $controller->index(new Request('GET', '/admin/analytics', query: ['range' => '7 days; DROP TABLE products'])); $assert($response->status === 200 && preg_match('/class="current" href="\/admin\/analytics\?range=30"[^>]*aria-current="page"/', $response->body) === 1, 'Unexpected status or selected range.'); });
    $test('Analytics output escapes stored catalog values', static function () use ($assert, $controller): void { $body = $controller->index(new Request('GET', '/admin/analytics', query: ['range' => 'all']))->body; $assert(!str_contains($body, '<img src=x') && str_contains($body, '&lt;img src=x onerror=alert(1)&gt;') && !str_contains($body, '<script>Brand')); });
    $test('Admin analytics route blocks guests and permits authentication', static function () use ($assert, $controller): void {
        $router = new Router(); $router->get('/admin/analytics', [$controller, 'index']); $state = (object) ['authenticated' => false];
        $router->protectAdminWith(static fn (): ?Response => $state->authenticated ? null : Response::redirect('/admin/login'));
        $blocked = $router->dispatch(new Request('GET', '/admin/analytics')); $assert(in_array($blocked->status, [302, 303], true), 'Guest status was ' . $blocked->status); $state->authenticated = true; $allowed = $router->dispatch(new Request('GET', '/admin/analytics')); $assert($allowed->status === 200, 'Authenticated status was ' . $allowed->status);
    });
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
if ($failures !== []) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "All Phase 6 analytics checks passed.\n";
