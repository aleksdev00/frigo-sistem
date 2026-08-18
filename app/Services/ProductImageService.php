<?php

declare(strict_types=1);

namespace App\Services;

use App\Foundation\Logger;
use App\Repositories\ProductImageRepository;

final readonly class ProductImageService
{
    private const MAX_FILES_PER_REQUEST = 10;
    public function __construct(private ProductImageRepository $images,private ImageProcessor $processor,private Logger $logger,private string $publicPath) {}

    public function upload(int $productId,string $productName,array $files): int
    {
        $uploads=$this->normalize($files);
        if ($uploads===[]) throw new ImageProcessingException('Choose at least one image.');
        if (count($uploads)>self::MAX_FILES_PER_REQUEST) throw new ImageProcessingException('Upload no more than 10 images at once.');
        $directory=$this->directory($productId);
        if (!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) throw new ImageProcessingException('Image storage is unavailable.');
        $stored=[];
        try {
            foreach ($uploads as $upload) {
                $filename=bin2hex(random_bytes(16)).'.webp'; $absolute=$directory.DIRECTORY_SEPARATOR.$filename;
                $dimensions=$this->processor->process($upload,$absolute);
                $stored[]=['absolute'=>$absolute,'filename'=>$filename,...$dimensions];
            }
            $this->images->begin();
            $hasMain=$this->images->hasMain($productId); $order=$this->images->nextSortOrder($productId);
            foreach ($stored as $index=>$file) $this->images->insert($productId,'uploads/products/'.$productId.'/'.$file['filename'],$this->altText($productName),!$hasMain && $index===0,$order+$index,$file['width'],$file['height']);
            $this->images->commit();
        } catch (\Throwable $exception) {
            $this->images->rollBack();
            foreach ($stored as $file) if (is_file($file['absolute'])) @unlink($file['absolute']);
            throw $exception;
        }
        return count($stored);
    }

    public function setMain(int $productId,int $imageId): bool
    {
        if ($this->images->findForProduct($imageId,$productId)===null) return false;
        $this->images->begin();
        try { $this->images->clearMain($productId); $ok=$this->images->setMain($imageId,$productId); $this->images->commit(); return $ok; }
        catch (\Throwable $exception) { $this->images->rollBack(); throw $exception; }
    }

    public function reorder(int $productId,array $ids): bool
    {
        $submitted=[];
        foreach ($ids as $id) { $value=filter_var($id,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); if ($value===false || isset($submitted[(int)$value])) return false; $submitted[(int)$value]=true; }
        $existing=array_map(static fn(array $row):int=>(int)$row['id'],$this->images->allForProduct($productId));
        $ordered=array_keys($submitted);
        if (count($ordered)!==count($existing) || array_diff($ordered,$existing)!==[] || array_diff($existing,$ordered)!==[]) return false;
        $this->images->begin();
        try { foreach ($ordered as $order=>$id) $this->images->updateOrder($id,$productId,$order); $this->images->commit(); return true; }
        catch (\Throwable $exception) { $this->images->rollBack(); throw $exception; }
    }

    public function delete(int $productId,int $imageId): bool
    {
        $image=$this->images->findForProduct($imageId,$productId); if ($image===null) return false;
        $this->images->begin();
        try {
            if (!$this->images->delete($imageId,$productId)) { $this->images->rollBack(); return false; }
            if ((int)$image['is_main']===1) { $remaining=$this->images->allForProduct($productId); if ($remaining!==[]) $this->images->setMain((int)$remaining[0]['id'],$productId); }
            $this->images->commit();
        } catch (\Throwable $exception) { $this->images->rollBack(); throw $exception; }
        $this->cleanupFiles([(string)$image['image_path']],$productId);
        return true;
    }

    public function cleanupFiles(array $paths,int $productId): void
    {
        foreach ($paths as $path) $this->removeRelative((string)$path,$productId);
        $directory=$this->directory($productId); if (is_dir($directory) && !$this->hasEntries($directory) && !@rmdir($directory)) $this->logger->error('Empty product upload directory could not be removed.',['product_id'=>$productId]);
    }

    private function removeRelative(string $relative,int $productId): void
    {
        if (!preg_match('#^uploads/products/'.preg_quote((string)$productId,'#').'/[a-f0-9]{32}\.webp$#D',$relative)) { $this->logger->error('Unsafe product image path was not removed.',['product_id'=>$productId]); return; }
        $absolute=$this->publicPath.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
        if (is_file($absolute) && !@unlink($absolute)) $this->logger->error('Product image file cleanup failed.',['product_id'=>$productId]);
    }

    private function normalize(array $files): array
    {
        if (!isset($files['error'])) return [];
        if (!is_array($files['error'])) return [$files];
        $result=[];
        foreach ($files['error'] as $index=>$error) $result[]=['name'=>$files['name'][$index]??'','type'=>$files['type'][$index]??'','tmp_name'=>$files['tmp_name'][$index]??'','error'=>$error,'size'=>$files['size'][$index]??0];
        return $result;
    }

    private function directory(int $productId): string { return $this->publicPath.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR.$productId; }
    private function altText(string $name): string { $value=trim($name).' klima uređaj'; return function_exists('mb_substr')?mb_substr($value,0,255):substr($value,0,255); }
    private function hasEntries(string $directory): bool { $items=scandir($directory); return is_array($items) && array_diff($items,['.','..'])!==[]; }
}
