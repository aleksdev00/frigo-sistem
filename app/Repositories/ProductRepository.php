<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class ProductRepository
{
    public function __construct(private PDO $pdo) {}

    public function paginate(array $filters, int $page, int $perPage = 20): array
    {
        [$where, $params] = $this->filters($filters);
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM products p ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT p.id,p.name,p.slug,p.code,p.price,p.is_active,p.is_featured,p.updated_at,b.name AS brand_name,c.name AS category_name '
            . 'FROM products p JOIN brands b ON b.id=p.brand_id JOIN categories c ON c.id=p.category_id '
            . $where . ' ORDER BY p.updated_at DESC,p.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return ['items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function find(int $id): ?array
    {
        $s=$this->pdo->prepare('SELECT * FROM products WHERE id=:id LIMIT 1'); $s->execute(['id'=>$id]); $row=$s->fetch(); return is_array($row)?$row:null;
    }
    public function slugExists(string $slug,?int $exceptId=null): bool
    {
        $sql='SELECT 1 FROM products WHERE slug=:slug'.($exceptId===null?'':' AND id<>:id').' LIMIT 1'; $s=$this->pdo->prepare($sql); $p=['slug'=>$slug]; if($exceptId!==null)$p['id']=$exceptId; $s->execute($p); return $s->fetchColumn()!==false;
    }
    public function create(array $data): int
    {
        $s=$this->pdo->prepare('INSERT INTO products (brand_id,category_id,name,slug,code,price,short_description,description,seo_title,seo_description,is_featured,is_active,created_at,updated_at) VALUES (:brand_id,:category_id,:name,:slug,:code,:price,:short_description,:description,:seo_title,:seo_description,:is_featured,:is_active,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
        $s->execute($data); return (int)$this->pdo->lastInsertId();
    }
    public function update(int $id,array $data): void
    {
        $s=$this->pdo->prepare('UPDATE products SET brand_id=:brand_id,category_id=:category_id,name=:name,slug=:slug,code=:code,price=:price,short_description=:short_description,description=:description,seo_title=:seo_title,seo_description=:seo_description,is_featured=:is_featured,is_active=:is_active,updated_at=CURRENT_TIMESTAMP WHERE id=:id');
        $s->execute([...$data,'id'=>$id]);
    }
    public function setStatus(int $id,bool $active): bool { $s=$this->pdo->prepare('UPDATE products SET is_active=:active,updated_at=CURRENT_TIMESTAMP WHERE id=:id'); $s->execute(['active'=>(int)$active,'id'=>$id]); return $s->rowCount()>0; }
    public function delete(int $id): bool { $s=$this->pdo->prepare('DELETE FROM products WHERE id=:id'); $s->execute(['id'=>$id]); return $s->rowCount()>0; }
    public function deleteWithImagePaths(int $id): ?array
    {
        $this->pdo->beginTransaction();
        try {
            $paths=$this->pdo->prepare('SELECT image_path FROM product_images WHERE product_id=:id FOR UPDATE'); $paths->execute(['id'=>$id]);
            $files=array_column($paths->fetchAll(),'image_path');
            $delete=$this->pdo->prepare('DELETE FROM products WHERE id=:id'); $delete->execute(['id'=>$id]);
            if ($delete->rowCount()!==1) { $this->pdo->rollBack(); return null; }
            $this->pdo->commit(); return $files;
        } catch (\Throwable $exception) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $exception; }
    }
    public function counts(): array
    {
        $row=$this->pdo->query('SELECT COUNT(*) AS total,COALESCE(SUM(is_active=1),0) AS active,COALESCE(SUM(is_active=0),0) AS hidden FROM products')->fetch();
        return ['total'=>(int)$row['total'],'active'=>(int)$row['active'],'hidden'=>(int)$row['hidden']];
    }
    public function recent(int $limit=5): array
    {
        $limit=max(1,min(10,$limit)); return $this->pdo->query('SELECT id,name,is_active,updated_at FROM products ORDER BY updated_at DESC,id DESC LIMIT '.$limit)->fetchAll();
    }

    private function filters(array $filters): array
    {
        $conditions=[]; $params=[];
        if(($filters['q']??'')!==''){ $conditions[]='(p.name LIKE :search_name OR p.code LIKE :search_code)'; $params['search_name']='%'.$filters['q'].'%'; $params['search_code']='%'.$filters['q'].'%'; }
        if(($filters['brand_id']??0)>0){ $conditions[]='p.brand_id=:brand_id'; $params['brand_id']=$filters['brand_id']; }
        if(($filters['category_id']??0)>0){ $conditions[]='p.category_id=:category_id'; $params['category_id']=$filters['category_id']; }
        if(($filters['status']??'')==='active')$conditions[]='p.is_active=1';
        if(($filters['status']??'')==='hidden')$conditions[]='p.is_active=0';
        return [$conditions===[]?'':'WHERE '.implode(' AND ',$conditions),$params];
    }
}
