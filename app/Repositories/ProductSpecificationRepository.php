<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class ProductSpecificationRepository
{
    public function __construct(private PDO $pdo) {}

    public function allForProduct(int $productId): array
    {
        $statement=$this->pdo->prepare('SELECT id,name,value,sort_order FROM product_specifications WHERE product_id=:product_id ORDER BY sort_order,id');
        $statement->execute(['product_id'=>$productId]);
        return $statement->fetchAll();
    }

    public function replaceAll(int $productId, array $rows): void
    {
        $this->pdo->beginTransaction();
        try {
            $delete=$this->pdo->prepare('DELETE FROM product_specifications WHERE product_id=:product_id');
            $delete->execute(['product_id'=>$productId]);
            $insert=$this->pdo->prepare('INSERT INTO product_specifications (product_id,name,value,sort_order,created_at,updated_at) VALUES (:product_id,:name,:value,:sort_order,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
            foreach ($rows as $order=>$row) {
                $insert->execute(['product_id'=>$productId,'name'=>$row['name'],'value'=>$row['value'],'sort_order'=>$order]);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
