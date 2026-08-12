<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
Environment::load($basePath . '/.env');

$config = new Config(['database' => require $basePath . '/config/database.php']);
$databaseName = (string) $config->get('database.database');
$pdo = (new Database($config))->connect();
$expectedTables = [
    'admins',
    'brands',
    'categories',
    'login_throttles',
    'product_images',
    'product_specifications',
    'product_views',
    'products',
];
$expectedInfrastructureTables = ['schema_migrations'];

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$expectIntegrityFailure = static function (callable $operation, string $message) use ($assert): void {
    try {
        $operation();
    } catch (PDOException $exception) {
        $assert($exception->getCode() === '23000', $message . ' Unexpected SQLSTATE: ' . $exception->getCode());
        return;
    }
    throw new RuntimeException($message);
};

try {
    $connectionCharset = $pdo->query('SELECT @@character_set_connection')->fetchColumn();
    $assert($connectionCharset === 'utf8mb4', 'PDO connection does not use utf8mb4.');

    $statement = $pdo->prepare(
        'SELECT TABLE_NAME AS table_name, ENGINE AS engine, TABLE_COLLATION AS table_collation '
        . 'FROM information_schema.tables '
        . 'WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = \'BASE TABLE\' ORDER BY TABLE_NAME',
    );
    $statement->execute(['schema' => $databaseName]);
    $tables = $statement->fetchAll();
    $allTables = array_column($tables, 'table_name');
    $actualTables = array_values(array_diff($allTables, $expectedInfrastructureTables));
    $assert(
        $actualTables === $expectedTables,
        'Database tables differ from the expected Phase 2 table set. Actual: ' . json_encode($actualTables),
    );
    $assert(
        array_values(array_intersect($allTables, $expectedInfrastructureTables)) === $expectedInfrastructureTables,
        'The schema_migrations infrastructure table is missing.',
    );
    foreach ($tables as $table) {
        $assert($table['engine'] === 'InnoDB', $table['table_name'] . ' does not use InnoDB.');
        $assert(str_starts_with($table['table_collation'], 'utf8mb4_'), $table['table_name'] . ' does not use utf8mb4.');
    }

    $expectedIndexes = [
        'admins' => ['PRIMARY', 'uq_admins_username'],
        'login_throttles' => ['PRIMARY', 'idx_login_throttles_updated_at'],
        'brands' => ['PRIMARY', 'uq_brands_name', 'uq_brands_slug'],
        'categories' => ['PRIMARY', 'uq_categories_name', 'uq_categories_slug'],
        'products' => ['PRIMARY', 'idx_products_brand_id', 'idx_products_category_id', 'idx_products_code', 'idx_products_is_active', 'uq_products_slug'],
        'product_images' => ['PRIMARY', 'idx_product_images_product_id'],
        'product_specifications' => ['PRIMARY', 'idx_product_specifications_product_id'],
        'product_views' => ['PRIMARY', 'idx_product_views_product_viewed_at', 'idx_product_views_viewed_at'],
        'schema_migrations' => ['PRIMARY', 'uq_schema_migrations_migration'],
    ];
    $statement = $pdo->prepare(
        'SELECT TABLE_NAME AS table_name, INDEX_NAME AS index_name, MIN(NON_UNIQUE) AS non_unique, '
        . 'GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_in_order '
        . 'FROM information_schema.statistics WHERE TABLE_SCHEMA = :schema '
        . 'GROUP BY TABLE_NAME, INDEX_NAME',
    );
    $statement->execute(['schema' => $databaseName]);
    $indexes = [];
    $indexDetails = [];
    foreach ($statement->fetchAll() as $index) {
        $indexes[$index['table_name']][] = $index['index_name'];
        $indexDetails[$index['table_name']][$index['index_name']] = [
            'non_unique' => (int) $index['non_unique'],
            'columns' => $index['columns_in_order'],
        ];
    }
    foreach ($expectedIndexes as $table => $names) {
        sort($indexes[$table]);
        sort($names);
        $assert($indexes[$table] === $names, 'Indexes differ for ' . $table . '.');
    }
    foreach ([
        'admins' => ['uq_admins_username'],
        'brands' => ['uq_brands_name', 'uq_brands_slug'],
        'categories' => ['uq_categories_name', 'uq_categories_slug'],
        'products' => ['uq_products_slug'],
        'schema_migrations' => ['uq_schema_migrations_migration'],
    ] as $table => $uniqueIndexes) {
        foreach ($uniqueIndexes as $indexName) {
            $assert($indexDetails[$table][$indexName]['non_unique'] === 0, $indexName . ' is not unique.');
        }
    }
    $assert(
        $indexDetails['product_views']['idx_product_views_product_viewed_at']['columns'] === 'product_id,viewed_at',
        'The product view compound index has the wrong column order.',
    );

    $expectedForeignKeys = [
        'fk_product_images_product' => ['product_images', 'product_id', 'products', 'id', 'CASCADE'],
        'fk_product_specifications_product' => ['product_specifications', 'product_id', 'products', 'id', 'CASCADE'],
        'fk_product_views_product' => ['product_views', 'product_id', 'products', 'id', 'CASCADE'],
        'fk_products_brand' => ['products', 'brand_id', 'brands', 'id', 'RESTRICT'],
        'fk_products_category' => ['products', 'category_id', 'categories', 'id', 'RESTRICT'],
    ];
    $statement = $pdo->prepare(
        'SELECT rc.CONSTRAINT_NAME AS constraint_name, kcu.TABLE_NAME AS table_name, '
        . 'kcu.COLUMN_NAME AS column_name, kcu.REFERENCED_TABLE_NAME AS referenced_table_name, '
        . 'kcu.REFERENCED_COLUMN_NAME AS referenced_column_name, rc.DELETE_RULE AS delete_rule '
        . 'FROM information_schema.referential_constraints rc '
        . 'JOIN information_schema.key_column_usage kcu ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA '
        . 'AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME AND kcu.TABLE_NAME = rc.TABLE_NAME '
        . 'WHERE rc.CONSTRAINT_SCHEMA = :schema ORDER BY rc.CONSTRAINT_NAME',
    );
    $statement->execute(['schema' => $databaseName]);
    $foreignKeys = [];
    foreach ($statement->fetchAll() as $foreignKey) {
        $foreignKeys[$foreignKey['constraint_name']] = [
            $foreignKey['table_name'],
            $foreignKey['column_name'],
            $foreignKey['referenced_table_name'],
            $foreignKey['referenced_column_name'],
            $foreignKey['delete_rule'],
        ];
    }
    $assert($foreignKeys === $expectedForeignKeys, 'Foreign keys differ from the documented relationships.');

    $migrationRows = $pdo->query(
        "SELECT migration FROM schema_migrations WHERE migration = '001_initial_schema.sql'",
    )->fetchAll(PDO::FETCH_COLUMN);
    $assert($migrationRows === ['001_initial_schema.sql'], 'The initial migration is not recorded exactly once.');

    $beforeRerun = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($basePath . '/database/migrate.php') . ' 2>&1';
    exec($command, $firstOutput, $firstExitCode);
    exec($command, $secondOutput, $secondExitCode);
    $afterRerun = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    $assert($firstExitCode === 0 && $secondExitCode === 0, 'Repeated migration runner execution failed.');
    $assert($beforeRerun === $afterRerun, 'Repeated migration execution changed migration history.');
    $assert(
        str_contains(implode("\n", $secondOutput), 'Skipped 001_initial_schema.sql (already applied).'),
        'Repeated migration execution did not report the initial migration as skipped.',
    );

    $pdo->beginTransaction();
    $now = '2026-08-12 12:00:00';
    $insertBrand = $pdo->prepare('INSERT INTO brands (name, slug, created_at, updated_at) VALUES (?, ?, ?, ?)');
    $insertBrand->execute(['Phase 2 Test Brand', 'phase-2-test-brand', $now, $now]);
    $brandId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO categories (name, slug, created_at, updated_at) VALUES (?, ?, ?, ?)')
        ->execute(['Phase 2 Test Category', 'phase-2-test-category', $now, $now]);
    $categoryId = (int) $pdo->lastInsertId();
    $insertProduct = $pdo->prepare(
        'INSERT INTO products (brand_id, category_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
    );
    $insertProduct->execute([$brandId, $categoryId, 'Phase 2 Test Product', 'phase-2-test-product', $now, $now]);
    $productId = (int) $pdo->lastInsertId();

    $expectIntegrityFailure(
        static fn () => $insertProduct->execute([$brandId, $categoryId, 'Duplicate Slug', 'phase-2-test-product', $now, $now]),
        'Duplicate product slug was accepted.',
    );
    $expectIntegrityFailure(
        static fn () => $insertProduct->execute([PHP_INT_MAX, $categoryId, 'Invalid Brand', 'invalid-brand', $now, $now]),
        'Product with invalid brand was accepted.',
    );
    $expectIntegrityFailure(
        static fn () => $insertProduct->execute([$brandId, PHP_INT_MAX, 'Invalid Category', 'invalid-category', $now, $now]),
        'Product with invalid category was accepted.',
    );
    $expectIntegrityFailure(static fn () => $pdo->exec('DELETE FROM brands WHERE id = ' . $brandId), 'Referenced brand was deleted.');
    $expectIntegrityFailure(static fn () => $pdo->exec('DELETE FROM categories WHERE id = ' . $categoryId), 'Referenced category was deleted.');

    $pdo->prepare('INSERT INTO product_images (product_id, image_path, created_at) VALUES (?, ?, ?)')
        ->execute([$productId, 'tests/image.webp', $now]);
    $pdo->prepare('INSERT INTO product_specifications (product_id, name, value, created_at, updated_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$productId, 'Test', 'Value', $now, $now]);
    $pdo->prepare('INSERT INTO product_views (product_id, viewed_at) VALUES (?, ?)')->execute([$productId, $now]);
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$productId]);
    foreach (['product_images', 'product_specifications', 'product_views'] as $childTable) {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $childTable)->fetchColumn();
        $assert($count === 0, 'Cascade delete failed for ' . $childTable . '.');
    }
    $pdo->rollBack();

    echo "PASS: PDO connection uses utf8mb4.\n";
    echo "PASS: Expected seven-table application schema, infrastructure table, InnoDB, and utf8mb4 collations verified.\n";
    echo "PASS: Initial migration history and safe repeated runner execution verified.\n";
    echo "PASS: Required indexes and unique constraints verified.\n";
    echo "PASS: Foreign-key definitions, invalid references, RESTRICT, and CASCADE behavior verified.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Database verification failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
