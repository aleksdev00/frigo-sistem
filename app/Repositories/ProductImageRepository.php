<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class ProductImageRepository
{
    public function __construct(private PDO $pdo) {}

    public function allForProduct(int $productId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM product_images WHERE product_id=:product_id ORDER BY sort_order,id');
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }

    public function findForProduct(int $id, int $productId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM product_images WHERE id=:id AND product_id=:product_id LIMIT 1');
        $statement->execute(['id' => $id, 'product_id' => $productId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function nextSortOrder(int $productId): int
    {
        $statement = $this->pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM product_images WHERE product_id=:product_id');
        $statement->execute(['product_id' => $productId]);
        return (int) $statement->fetchColumn();
    }

    public function hasMain(int $productId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM product_images WHERE product_id=:product_id AND is_main=1 LIMIT 1');
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchColumn() !== false;
    }

    public function insert(int $productId, string $path, string $altText, bool $main, int $sortOrder, int $width, int $height): int
    {
        $statement = $this->pdo->prepare('INSERT INTO product_images (product_id,image_path,alt_text,is_main,sort_order,width,height,created_at) VALUES (:product_id,:image_path,:alt_text,:is_main,:sort_order,:width,:height,CURRENT_TIMESTAMP)');
        $statement->execute(['product_id'=>$productId,'image_path'=>$path,'alt_text'=>$altText,'is_main'=>(int)$main,'sort_order'=>$sortOrder,'width'=>$width,'height'=>$height]);
        return (int) $this->pdo->lastInsertId();
    }

    public function clearMain(int $productId): void
    {
        $statement = $this->pdo->prepare('UPDATE product_images SET is_main=0 WHERE product_id=:product_id');
        $statement->execute(['product_id' => $productId]);
    }

    public function setMain(int $id, int $productId): bool
    {
        $statement = $this->pdo->prepare('UPDATE product_images SET is_main=1 WHERE id=:id AND product_id=:product_id');
        $statement->execute(['id'=>$id,'product_id'=>$productId]);
        return $statement->rowCount() === 1;
    }

    public function delete(int $id, int $productId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM product_images WHERE id=:id AND product_id=:product_id');
        $statement->execute(['id'=>$id,'product_id'=>$productId]);
        return $statement->rowCount() === 1;
    }

    public function updateOrder(int $id, int $productId, int $order): bool
    {
        $statement = $this->pdo->prepare('UPDATE product_images SET sort_order=:sort_order WHERE id=:id AND product_id=:product_id');
        $statement->execute(['sort_order'=>$order,'id'=>$id,'product_id'=>$productId]);
        return $statement->rowCount() <= 1;
    }

    public function begin(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); }
}
