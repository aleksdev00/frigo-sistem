<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class CategoryRepository
{
    public function __construct(private PDO $pdo) {}

    public function allWithProductCounts(): array
    {
        return $this->pdo->query(
            'SELECT c.id, c.name, c.slug, c.description, c.seo_title, c.seo_description, c.is_active, c.updated_at, '
            . 'COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id '
            . 'GROUP BY c.id ORDER BY c.name',
        )->fetchAll();
    }

    public function all(): array { return $this->pdo->query('SELECT id, name, is_active FROM categories ORDER BY name')->fetchAll(); }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM categories WHERE id=:id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function exists(int $id): bool { $s=$this->pdo->prepare('SELECT 1 FROM categories WHERE id=:id'); $s->execute(['id'=>$id]); return $s->fetchColumn() !== false; }
    public function nameExists(string $name, ?int $exceptId = null): bool { return $this->valueExists('name', $name, $exceptId); }
    public function slugExists(string $slug, ?int $exceptId = null): bool { return $this->valueExists('slug', $slug, $exceptId); }

    public function create(array $data): int
    {
        $s=$this->pdo->prepare('INSERT INTO categories (name,slug,description,seo_title,seo_description,is_active,created_at,updated_at) VALUES (:name,:slug,:description,:seo_title,:seo_description,:is_active,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
        $s->execute($data); return (int)$this->pdo->lastInsertId();
    }
    public function update(int $id,array $data): void
    {
        $s=$this->pdo->prepare('UPDATE categories SET name=:name,slug=:slug,description=:description,seo_title=:seo_title,seo_description=:seo_description,is_active=:is_active,updated_at=CURRENT_TIMESTAMP WHERE id=:id');
        $s->execute([...$data,'id'=>$id]);
    }
    public function setStatus(int $id,bool $active): bool { $s=$this->pdo->prepare('UPDATE categories SET is_active=:active,updated_at=CURRENT_TIMESTAMP WHERE id=:id'); $s->execute(['active'=>(int)$active,'id'=>$id]); return $s->rowCount()>0; }
    public function productCount(int $id): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id=:id'); $s->execute(['id'=>$id]); return (int)$s->fetchColumn(); }
    public function delete(int $id): bool { $s=$this->pdo->prepare('DELETE FROM categories WHERE id=:id'); $s->execute(['id'=>$id]); return $s->rowCount()>0; }
    public function count(): int { return (int)$this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(); }

    private function valueExists(string $column,string $value,?int $exceptId): bool
    {
        $sql="SELECT 1 FROM categories WHERE {$column}=:value".($exceptId===null?'':' AND id<>:id').' LIMIT 1';
        $s=$this->pdo->prepare($sql); $params=['value'=>$value]; if($exceptId!==null)$params['id']=$exceptId; $s->execute($params); return $s->fetchColumn()!==false;
    }
}
