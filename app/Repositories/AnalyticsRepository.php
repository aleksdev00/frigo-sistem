<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;

final readonly class AnalyticsRepository
{
    public function __construct(private PDO $pdo) {}

    public function productExists(int $productId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $productId]);
        return $statement->fetchColumn() !== false;
    }

    public function hasRecentView(int $productId, string $visitorTokenHash, DateTimeImmutable $since): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM product_views WHERE product_id = :product_id AND visitor_token_hash = :visitor_token_hash AND viewed_at >= :since LIMIT 1');
        $statement->execute(['product_id' => $productId, 'visitor_token_hash' => $visitorTokenHash, 'since' => $since->format('Y-m-d H:i:s')]);
        return $statement->fetchColumn() !== false;
    }

    public function insertView(int $productId, string $visitorTokenHash, DateTimeImmutable $viewedAt): void
    {
        $statement = $this->pdo->prepare('INSERT INTO product_views (product_id, viewed_at, visitor_token_hash) VALUES (:product_id, :viewed_at, :visitor_token_hash)');
        $statement->execute(['product_id' => $productId, 'viewed_at' => $viewedAt->format('Y-m-d H:i:s'), 'visitor_token_hash' => $visitorTokenHash]);
    }

    public function summary(?DateTimeImmutable $since): array
    {
        [$where, $params] = $this->dateFilter($since);
        $statement = $this->pdo->prepare('SELECT COUNT(*) AS total_views, COUNT(DISTINCT product_id) AS viewed_products FROM product_views' . $where);
        $statement->execute($params);
        $row = $statement->fetch();
        return ['total_views' => (int) $row['total_views'], 'viewed_products' => (int) $row['viewed_products']];
    }

    public function topProducts(?DateTimeImmutable $since, int $limit = 10): array
    {
        $limit = max(1, min(25, $limit));
        [$where, $params] = $this->dateFilter($since, 'pv');
        $statement = $this->pdo->prepare('SELECT p.id, p.name, b.name AS brand_name, c.name AS category_name, COUNT(*) AS views FROM product_views pv JOIN products p ON p.id = pv.product_id JOIN brands b ON b.id = p.brand_id JOIN categories c ON c.id = p.category_id' . $where . ' GROUP BY p.id, p.name, b.name, c.name ORDER BY views DESC, p.name ASC LIMIT ' . $limit);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function topCategories(?DateTimeImmutable $since, int $limit = 10): array
    {
        $limit = max(1, min(25, $limit));
        [$where, $params] = $this->dateFilter($since, 'pv');
        $statement = $this->pdo->prepare('SELECT c.id, c.name, COUNT(*) AS views FROM product_views pv JOIN products p ON p.id = pv.product_id JOIN categories c ON c.id = p.category_id' . $where . ' GROUP BY c.id, c.name ORDER BY views DESC, c.name ASC LIMIT ' . $limit);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function dailyViews(?DateTimeImmutable $since, int $limit = 3660): array
    {
        $limit = max(1, min(3660, $limit));
        [$where, $params] = $this->dateFilter($since);
        $statement = $this->pdo->prepare('SELECT DATE(viewed_at) AS view_date, COUNT(*) AS views FROM product_views' . $where . ' GROUP BY DATE(viewed_at) ORDER BY view_date DESC LIMIT ' . $limit);
        $statement->execute($params);
        return array_reverse($statement->fetchAll());
    }

    private function dateFilter(?DateTimeImmutable $since, string $alias = ''): array
    {
        if ($since === null) {
            return ['', []];
        }
        $column = $alias === '' ? 'viewed_at' : $alias . '.viewed_at';
        return [' WHERE ' . $column . ' >= :since', ['since' => $since->format('Y-m-d H:i:s')]];
    }
}
