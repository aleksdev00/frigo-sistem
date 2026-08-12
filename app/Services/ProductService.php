<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Validation\ValidationResult;

final readonly class ProductService
{
    public function __construct(private ProductRepository $products,private BrandRepository $brands,private CategoryRepository $categories,private SlugService $slugs) {}
    public function validate(array $input,?int $id=null): ValidationResult
    {
        $name=$this->text($input['name']??'');
        $existing=$id===null?null:$this->products->find($id);
        $slug=$existing===null?$this->uniqueSlug($this->slugs->generate($name)):(string)$existing['slug'];
        $brandId=$this->positiveInt($input['brand_id']??null);$categoryId=$this->positiveInt($input['category_id']??null);$price=$this->text($input['price']??'');
        $data=['brand_id'=>$brandId,'category_id'=>$categoryId,'name'=>$name,'slug'=>$slug,'code'=>$this->nullable($input['code']??''),'price'=>$price===''?null:$price,'short_description'=>$this->nullable($input['short_description']??''),'description'=>$this->nullable($input['description']??''),'seo_title'=>$existing['seo_title']??null,'seo_description'=>$existing['seo_description']??null,'is_featured'=>isset($input['is_featured'])?1:0,'is_active'=>isset($input['is_active'])?1:0];$errors=[];
        if($name==='')$errors['name']='Name is required.';elseif($this->length($name)>255)$errors['name']='Name must not exceed 255 characters.';
        if($brandId===0||!$this->brands->exists($brandId))$errors['brand_id']='Select a valid existing brand.';
        if($categoryId===0||!$this->categories->exists($categoryId))$errors['category_id']='Select a valid existing category.';
        if($slug===''||$this->length($slug)>255||!$this->slugs->isValid($slug))$errors['name']='Name must contain characters that can form a safe product URL.';
        if($price!==''&&(!is_numeric($price)||(float)$price<0||(float)$price>9999999999.99))$errors['price']='Price must be a non-negative number within the supported range.';
        foreach(['code'=>150,'short_description'=>2000,'description'=>50000,'seo_title'=>255,'seo_description'=>320] as $field=>$max){if($data[$field]!==null&&$this->length((string)$data[$field])>$max)$errors[$field]=ucfirst(str_replace('_',' ',$field))." must not exceed {$max} characters.";}
        return new ValidationResult($data,$errors);
    }
    public function create(array $data):int{return $this->products->create($data);}public function update(int $id,array $data):void{$this->products->update($id,$data);}
    private function uniqueSlug(string $base):string
    {
        if($base===''||!$this->products->slugExists($base))return $base;
        for($suffix=2;$suffix<=999999;$suffix++){
            $candidate=substr($base,0,255-strlen((string)$suffix)-1).'-'.$suffix;
            if(!$this->products->slugExists($candidate))return $candidate;
        }
        throw new \RuntimeException('Unable to generate a unique product slug.');
    }
    private function text(mixed $v):string{return is_string($v)?trim($v):'';}private function nullable(mixed $v):?string{$v=$this->text($v);return $v===''?null:$v;}private function positiveInt(mixed $v):int{$value=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $value===false?0:$value;}private function length(string $v):int{return function_exists('mb_strlen')?mb_strlen($v):strlen($v);}
}
