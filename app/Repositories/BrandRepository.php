<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class BrandRepository
{
    public function __construct(private PDO $pdo) {}

    public function allWithProductCounts(): array
    {
        return $this->pdo->query(
            'SELECT b.id, b.name, b.slug, b.seo_title, b.seo_description, b.is_active, b.updated_at, '
            . 'COUNT(p.id) AS product_count FROM brands b LEFT JOIN products p ON p.brand_id = b.id '
            . 'GROUP BY b.id ORDER BY b.name',
        )->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, name, is_active FROM brands ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM brands WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function exists(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM brands WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetchColumn() !== false;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool { return $this->valueExists('name', $name, $exceptId); }
    public function slugExists(string $slug, ?int $exceptId = null): bool { return $this->valueExists('slug', $slug, $exceptId); }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO brands (name, slug, seo_title, seo_description, is_active, created_at, updated_at) '
            . 'VALUES (:name, :slug, :seo_title, :seo_description, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        );
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE brands SET name=:name, slug=:slug, seo_title=:seo_title, seo_description=:seo_description, '
            . 'is_active=:is_active, updated_at=CURRENT_TIMESTAMP WHERE id=:id',
        );
        $statement->execute([...$data, 'id' => $id]);
    }

    public function setStatus(int $id, bool $active): bool
    {
        $statement = $this->pdo->prepare('UPDATE brands SET is_active=:active, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
        $statement->execute(['active' => (int) $active, 'id' => $id]);
        return $statement->rowCount() > 0;
    }

    public function productCount(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE brand_id=:id');
        $statement->execute(['id' => $id]);
        return (int) $statement->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM brands WHERE id=:id');
        $statement->execute(['id' => $id]);
        return $statement->rowCount() > 0;
    }

    public function count(): int { return (int) $this->pdo->query('SELECT COUNT(*) FROM brands')->fetchColumn(); }

    private function valueExists(string $column, string $value, ?int $exceptId): bool
    {
        $sql = "SELECT 1 FROM brands WHERE {$column} = :value" . ($exceptId === null ? '' : ' AND id <> :id') . ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $params = ['value' => $value];
        if ($exceptId !== null) $params['id'] = $exceptId;
        $statement->execute($params);
        return $statement->fetchColumn() !== false;
    }
}
