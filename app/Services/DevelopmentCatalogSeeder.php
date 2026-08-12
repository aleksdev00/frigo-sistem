<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final readonly class DevelopmentCatalogSeeder
{
    public function __construct(private PDO $pdo) {}

    public function seed(array $data): void
    {
        $ownsTransaction=!$this->pdo->inTransaction();
        if($ownsTransaction)$this->pdo->beginTransaction();
        try{
            $brand=$this->pdo->prepare('INSERT INTO brands (name,slug,is_active,created_at,updated_at) VALUES (:name,:slug,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE id=id');
            foreach($data['brands']??[] as $item)$brand->execute(['name'=>$item['name'],'slug'=>$item['slug']]);
            $category=$this->pdo->prepare('INSERT INTO categories (name,slug,description,is_active,created_at,updated_at) VALUES (:name,:slug,:description,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE id=id');
            foreach($data['categories']??[] as $item)$category->execute(['name'=>$item['name'],'slug'=>$item['slug'],'description'=>$item['description']??null]);
            if($ownsTransaction)$this->pdo->commit();
        }catch(\Throwable $exception){if($ownsTransaction&&$this->pdo->inTransaction())$this->pdo->rollBack();throw $exception;}
    }
}
