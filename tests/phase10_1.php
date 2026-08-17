<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Http\Request;
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
    $brandName = 'Brand <Safe> ' . $suffix;
    $brandSlug = 'home-brand-' . $suffix;
    $pdo->prepare('INSERT INTO brands (name,slug,is_active,created_at,updated_at) VALUES (?,?,1,NOW(),NOW())')->execute([$brandName, $brandSlug]);
    $brandId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO categories (name,slug,is_active,created_at,updated_at) VALUES (?,?,1,NOW(),NOW())')->execute(['Home category', 'home-category-' . $suffix]);
    $categoryId = (int) $pdo->lastInsertId();
    $insert = $pdo->prepare('INSERT INTO products (brand_id,category_id,name,slug,code,is_featured,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
    $featuredSlug = 'home-featured-' . $suffix;
    $hiddenSlug = 'home-hidden-' . $suffix;
    $plainSlug = 'home-plain-' . $suffix;
    $insert->execute([$brandId, $categoryId, 'Featured <Unit>', $featuredSlug, 'F-1', 1, 1]);
    $insert->execute([$brandId, $categoryId, 'Hidden featured unit', $hiddenSlug, 'H-1', 1, 0]);
    $insert->execute([$brandId, $categoryId, 'Active not featured', $plainSlug, 'P-1', 0, 1]);

    $controller = new HomeController(new PublicCatalogRepository($pdo), new View($basePath . '/resources/views'), $config);
    $response = $controller->index(new Request('GET', '/'));
    $body = $response->body;
    $test('Homepage returns 200 and replaces the placeholder', static function () use ($assert, $response, $body): void { $assert($response->status === 200 && !str_contains($body, 'Nova početna stranica biće pripremljena')); });
    $test('Hero and contact CTAs use established routes', static function () use ($assert, $body): void { $assert(str_contains($body, 'href="/klima-uredjaji">Pogledajte klima uređaje</a>') && substr_count($body, 'href="/kontakt"') >= 3); });
    $test('Only active featured products appear with detail URLs', static function () use ($assert, $body, $featuredSlug, $hiddenSlug, $plainSlug): void { $assert(str_contains($body, '/klima-uredjaji/' . $featuredSlug) && !str_contains($body, $hiddenSlug) && !str_contains($body, $plainSlug)); });
    $test('Active database brands render with valid links', static function () use ($assert, $body, $brandSlug): void { $assert(str_contains($body, '/brend/' . $brandSlug)); });
    $test('Database output remains escaped', static function () use ($assert, $body, $brandName): void { $assert(str_contains($body, 'Brand &lt;Safe&gt;') && str_contains($body, 'Featured &lt;Unit&gt;') && !str_contains($body, $brandName)); });
    $test('Homepage preserves public layout and SEO metadata', static function () use ($assert, $body): void { $assert(str_contains($body, 'class="site-header"') && str_contains($body, 'class="site-footer"') && str_contains($body, 'rel="canonical" href="https://example.test/"') && str_contains($body, '<h1 id="page-title">')); });
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

if ($failures !== []) { fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL); exit(1); }
echo "All Phase 10.1 homepage checks passed.\n";
