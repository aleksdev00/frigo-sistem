<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class PublicProductRepository
{
    public function __construct(private PDO $pdo) {}

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id,p.name,p.slug,p.code,p.price,p.short_description,p.description,p.seo_title,p.seo_description,'
            . 'b.name AS brand_name,b.slug AS brand_slug,c.name AS category_name,c.slug AS category_slug '
            . 'FROM products p JOIN brands b ON b.id=p.brand_id AND b.is_active=1 '
            . 'JOIN categories c ON c.id=p.category_id AND c.is_active=1 '
            . 'WHERE p.slug=:slug AND p.is_active=1 LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function images(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT image_path,alt_text,is_main,sort_order,width,height FROM product_images '
            . 'WHERE product_id=:product_id ORDER BY is_main DESC,sort_order ASC,id ASC'
        );
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }

    public function specifications(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT name,value FROM product_specifications WHERE product_id=:product_id ORDER BY sort_order ASC,id ASC'
        );
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }

    public function related(int $productId, string $categorySlug, string $brandSlug, int $limit = 4): array
    {
        $limit = max(1, min(8, $limit));
        $statement = $this->pdo->prepare(
            'SELECT p.id,p.name,p.slug,p.code,p.price,p.short_description,p.is_featured,p.updated_at,'
            . 'b.name AS brand_name,b.slug AS brand_slug,c.name AS category_name,c.slug AS category_slug,'
            . 'pi.image_path,pi.alt_text AS image_alt,pi.width AS image_width,pi.height AS image_height '
            . 'FROM products p JOIN brands b ON b.id=p.brand_id AND b.is_active=1 '
            . 'JOIN categories c ON c.id=p.category_id AND c.is_active=1 '
            . 'LEFT JOIN product_images pi ON pi.id=(SELECT selected.id FROM product_images selected '
            . 'WHERE selected.product_id=p.id ORDER BY selected.is_main DESC,selected.sort_order ASC,selected.id ASC LIMIT 1) '
            . 'WHERE p.is_active=1 AND p.id<>:product_id AND (c.slug=:category_slug OR b.slug=:brand_slug) '
            . 'ORDER BY (c.slug=:category_order) DESC,(b.slug=:brand_order) DESC,p.is_featured DESC,p.updated_at DESC,p.id DESC LIMIT ' . $limit
        );
        $statement->execute([
            'product_id' => $productId,
            'category_slug' => $categorySlug,
            'brand_slug' => $brandSlug,
            'category_order' => $categorySlug,
            'brand_order' => $brandSlug,
        ]);
        return $statement->fetchAll();
    }
}
