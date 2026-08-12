<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductSpecificationRepository;
use App\Validation\ValidationResult;

final readonly class ProductSpecificationService
{
    public function __construct(private ProductSpecificationRepository $specifications) {}

    public function validate(array $input): ValidationResult
    {
        $names=is_array($input['spec_name']??null)?$input['spec_name']:[];
        $values=is_array($input['spec_value']??null)?$input['spec_value']:[];
        $orders=is_array($input['spec_order']??null)?$input['spec_order']:array_keys($names);
        $rows=[]; $errors=[];
        foreach ($names as $key=>$rawName) {
            $name=is_string($rawName)?trim($rawName):''; $value=is_string($values[$key]??null)?trim($values[$key]):'';
            if ($name==='' && $value==='') continue;
            if ($name==='') $errors['specifications']='Every specification needs a name.';
            elseif ($this->length($name)>190) $errors['specifications']='Specification names must not exceed 190 characters.';
            if ($value==='') $errors['specifications']='Every specification needs a value.';
            elseif ($this->length($value)>500) $errors['specifications']='Specification values must not exceed 500 characters.';
            $order=filter_var($orders[$key]??count($rows),FILTER_VALIDATE_INT); if ($order===false) $order=count($rows);
            $rows[]=['name'=>$name,'value'=>$value,'order'=>(int)$order,'sequence'=>count($rows)];
        }
        usort($rows,static fn(array $a,array $b):int=>[$a['order'],$a['sequence']]<=>[$b['order'],$b['sequence']]);
        $rows=array_map(static fn(array $row):array=>['name'=>$row['name'],'value'=>$row['value']],$rows);
        return new ValidationResult($rows,$errors);
    }

    public function replace(int $productId,array $rows): void { $this->specifications->replaceAll($productId,$rows); }
    private function length(string $value): int { return function_exists('mb_strlen')?mb_strlen($value):strlen($value); }
}
