<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BrandRepository;
use App\Validation\ValidationResult;
use PDOException;

final readonly class BrandService
{
    public function __construct(private BrandRepository $brands,private SlugService $slugs) {}

    public function validate(array $input,?int $id=null): ValidationResult
    {
        $name=$this->text($input['name']??''); $slug=$this->text($input['slug']??''); if($slug==='')$slug=$this->slugs->generate($name);
        $data=['name'=>$name,'slug'=>$slug,'seo_title'=>$this->nullable($input['seo_title']??''),'seo_description'=>$this->nullable($input['seo_description']??''),'is_active'=>isset($input['is_active'])?1:0]; $errors=[];
        if($name==='')$errors['name']='Name is required.'; elseif($this->length($name)>150)$errors['name']='Name must not exceed 150 characters.'; elseif($this->brands->nameExists($name,$id))$errors['name']='A brand with this name already exists.';
        if($slug==='')$errors['slug']='Slug is required.'; elseif($this->length($slug)>180||!$this->slugs->isValid($slug))$errors['slug']='Slug must contain only lowercase letters, numbers, and single hyphens.'; elseif($this->brands->slugExists($slug,$id))$errors['slug']='This slug is already in use.';
        if($data['seo_title']!==null&&$this->length($data['seo_title'])>255)$errors['seo_title']='SEO title must not exceed 255 characters.';
        if($data['seo_description']!==null&&$this->length($data['seo_description'])>320)$errors['seo_description']='SEO description must not exceed 320 characters.';
        return new ValidationResult($data,$errors);
    }
    public function create(array $data): int{return $this->brands->create($data);} public function update(int $id,array $data): void{$this->brands->update($id,$data);}
    public function delete(int $id): string { if($this->brands->productCount($id)>0)return 'referenced';try{return $this->brands->delete($id)?'deleted':'missing';}catch(PDOException $e){if($e->getCode()==='23000')return 'referenced';throw $e;} }
    private function text(mixed $v): string{return is_string($v)?trim($v):'';} private function nullable(mixed $v): ?string{$v=$this->text($v);return $v===''?null:$v;} private function length(string $v): int{return function_exists('mb_strlen')?mb_strlen($v):strlen($v);}
}
