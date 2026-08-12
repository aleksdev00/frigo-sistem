<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ProductSeoService
{
    public function title(array $product): string
    {
        $override=trim((string)($product['seo_title']??''));
        return $override!==''?$override:$this->limit(trim((string)($product['name']??'')).' klima | Frigo Sistem Niš',255);
    }

    public function description(array $product): string
    {
        $override=trim((string)($product['seo_description']??''));
        if($override!=='')return $override;
        $parts=array_values(array_filter([
            trim((string)($product['name']??'')),
            trim((string)($product['brand_name']??'')),
            trim((string)($product['category_name']??'')),
        ],static fn(string $value):bool=>$value!==''));
        return $this->limit(implode(' — ',$parts).'. Pogledajte detalje i kontaktirajte Frigo Sistem Niš za informacije i ugradnju.',320);
    }

    private function limit(string $value,int $length):string
    {
        return function_exists('mb_substr')?mb_substr($value,0,$length):substr($value,0,$length);
    }
}
