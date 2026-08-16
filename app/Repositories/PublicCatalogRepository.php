<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class PublicCatalogRepository
{
    public function __construct(private PDO $pdo) {}

    public function paginate(array $filters, int $page, int $perPage = 12): array
    {
        $perPage = max(1, min(48, $perPage));
        $page = max(1, min(10000, $page));
        [$where, $params] = $this->where($filters);
        $from = ' FROM products p JOIN brands b ON b.id=p.brand_id AND b.is_active=1 '
            . 'JOIN categories c ON c.id=p.category_id AND c.is_active=1 ';

        $count = $this->pdo->prepare('SELECT COUNT(*)' . $from . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT p.id,p.name,p.slug,p.code,p.price,p.short_description,p.is_featured,p.updated_at,'
            . 'b.name AS brand_name,b.slug AS brand_slug,c.name AS category_name,c.slug AS category_slug,'
            . 'pi.image_path,pi.alt_text AS image_alt,pi.width AS image_width,pi.height AS image_height'
            . $from . 'LEFT JOIN product_images pi ON pi.id=(SELECT selected.id FROM product_images selected '
            . 'WHERE selected.product_id=p.id ORDER BY selected.is_main DESC,selected.sort_order ASC,selected.id ASC LIMIT 1) '
            . $where . ' ORDER BY p.is_featured DESC,p.updated_at DESC,p.id DESC '
            . 'LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return ['items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function activeBrands(): array
    {
        return $this->pdo->query('SELECT b.name,b.slug FROM brands b WHERE b.is_active=1 '
            . 'AND EXISTS (SELECT 1 FROM products p JOIN categories c ON c.id=p.category_id AND c.is_active=1 '
            . 'WHERE p.brand_id=b.id AND p.is_active=1) ORDER BY b.name')->fetchAll();
    }

    public function activeCategories(): array
    {
        return $this->pdo->query('SELECT c.name,c.slug FROM categories c WHERE c.is_active=1 '
            . 'AND EXISTS (SELECT 1 FROM products p JOIN brands b ON b.id=p.brand_id AND b.is_active=1 '
            . 'WHERE p.category_id=c.id AND p.is_active=1) ORDER BY c.name')->fetchAll();
    }

    public function findPublicBrand(string $slug): ?array
    {
        return $this->findTaxonomy('brands', 'brand_id', $slug);
    }

    public function findPublicCategory(string $slug): ?array
    {
        return $this->findTaxonomy('categories', 'category_id', $slug);
    }

    private function findTaxonomy(string $table, string $foreignKey, string $slug): ?array
    {
        $description = $table === 'categories' ? 't.description,' : '';
        $otherJoin = $table === 'brands'
            ? 'JOIN categories x ON x.id=p.category_id AND x.is_active=1'
            : 'JOIN brands x ON x.id=p.brand_id AND x.is_active=1';
        $statement = $this->pdo->prepare('SELECT t.id,t.name,t.slug,' . $description
            . 't.seo_title,t.seo_description FROM ' . $table . ' t WHERE t.slug=:slug AND t.is_active=1 '
            . 'AND EXISTS (SELECT 1 FROM products p ' . $otherJoin . ' WHERE p.' . $foreignKey . '=t.id AND p.is_active=1) LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private function where(array $filters): array
    {
        $conditions = ['p.is_active=1'];
        $params = [];
        if (($filters['q'] ?? '') !== '') {
            $conditions[] = '(p.name LIKE :q_name OR b.name LIKE :q_brand OR p.code LIKE :q_code)';
            $like = '%' . $filters['q'] . '%';
            $params += ['q_name' => $like, 'q_brand' => $like, 'q_code' => $like];
        }
        if (($filters['brand'] ?? '') !== '') {
            $conditions[] = 'b.slug=:brand';
            $params['brand'] = $filters['brand'];
        }
        if (($filters['category'] ?? '') !== '') {
            $conditions[] = 'c.slug=:category';
            $params['category'] = $filters['category'];
        }
        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }
}
