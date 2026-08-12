<?php

declare(strict_types=1);

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Environment;
use App\Services\DevelopmentCatalogSeeder;

$basePath=dirname(__DIR__);
require $basePath.'/vendor/autoload.php';
Environment::load($basePath.'/.env');
$environment=Environment::get('APP_ENV','production');
if(!in_array($environment,['local','development','testing'],true)){
    fwrite(STDERR,"Development seeds may only run in local, development, or testing environments.\n");
    exit(1);
}
$config=new Config(['database'=>require $basePath.'/config/database.php']);
$pdo=(new Database($config))->connect();
(new DevelopmentCatalogSeeder($pdo))->seed(require $basePath.'/database/seeds/development_catalog.php');
echo "Development brands and categories seeded.\n";
