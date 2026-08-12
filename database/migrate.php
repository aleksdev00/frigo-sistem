<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

Environment::load($basePath . '/.env');
$config = new Config(['database' => require $basePath . '/config/database.php']);
$baseline = ($argv[1] ?? null) === '--baseline';

if (isset($argv[1]) && !$baseline) {
    fwrite(STDERR, "Usage: php database/migrate.php [--baseline]\n");
    exit(1);
}

try {
    $pdo = (new Database($config))->connect();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations ('
        . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, '
        . 'migration VARCHAR(255) NOT NULL, '
        . 'applied_at DATETIME NOT NULL, '
        . 'PRIMARY KEY (id), '
        . 'UNIQUE KEY uq_schema_migrations_migration (migration)'
        . ') ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    );

    $migrationPaths = glob($basePath . '/database/migrations/*.sql');
    if ($migrationPaths === false) {
        throw new RuntimeException('Unable to discover migration files.');
    }
    $migrationPaths = array_values(array_filter(
        $migrationPaths,
        static fn (string $path): bool => preg_match('/^\d{3}_[a-z0-9_]+\.sql$/', basename($path)) === 1,
    ));
    sort($migrationPaths, SORT_STRING);

    $applied = $pdo->query('SELECT migration FROM schema_migrations ORDER BY migration')->fetchAll(PDO::FETCH_COLUMN);
    $appliedLookup = array_fill_keys($applied, true);
    $recordMigration = $pdo->prepare(
        'INSERT INTO schema_migrations (migration, applied_at) VALUES (:migration, CURRENT_TIMESTAMP)',
    );

    echo sprintf("Connected to configured database %s.\n", (string) $config->get('database.database'));
    foreach ($migrationPaths as $migrationPath) {
        $migration = basename($migrationPath);
        if (isset($appliedLookup[$migration])) {
            echo sprintf("Skipped %s (already applied).\n", $migration);
            continue;
        }

        if ($baseline) {
            $recordMigration->execute(['migration' => $migration]);
            echo sprintf("Baselined %s (SQL was not executed).\n", $migration);
            continue;
        }

        $sql = file_get_contents($migrationPath);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException(sprintf('Migration %s is empty or unreadable.', $migration));
        }

        echo sprintf("Applying %s...\n", $migration);
        $pdo->exec($sql);
        $recordMigration->execute(['migration' => $migration]);
        echo sprintf("Applied and recorded %s.\n", $migration);
    }

    echo "Migration run completed successfully.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("Migration failed: %s\n", $exception->getMessage()));
    exit(1);
}
