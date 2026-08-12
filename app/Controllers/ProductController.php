<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;use App\Http\Response;use App\Repositories\BrandRepository;use App\Repositories\CategoryRepository;use App\Repositories\ProductRepository;use App\Services\ProductService;use App\Support\AdminPage;use App\Support\Flash;

final readonly class ProductController
{
    public function __construct(private ProductRepository $products,private BrandRepository $brands,private CategoryRepository $categories,private ProductService $service,private AdminPage $page,private Flash $flash){}
    public function index(Request $r):Response
    {
        $filters=['q'=>$this->bounded($r->query['q']??'',100),'brand_id'=>$this->positiveInt($r->query['brand_id']??null),'category_id'=>$this->positiveInt($r->query['category_id']??null),'status'=>in_array($r->query['status']??'',['active','hidden'],true)?$r->query['status']:''];$page=max(1,$this->positiveInt($r->query['page']??1));$result=$this->products->paginate($filters,$page);
        if($page>$result['pages'])return Response::redirect('/admin/products');
        return $this->page->render('admin/products/index','Products',['result'=>$result,'filters'=>$filters,'brands'=>$this->brands->all(),'categories'=>$this->categories->all()]);
    }
    public function create(Request $r):Response{return $this->form(['is_active'=>0,'is_featured'=>0],[],'Create product','/admin/products');}
    public function store(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$v=$this->service->validate($r->input);if(!$v->isValid())return $this->form($v->data,$v->errors,'Create product','/admin/products',422);$id=$this->service->create($v->data);$this->flash->success('Product created successfully.');return Response::redirect('/admin/products/'.$id.'/edit');}
    public function edit(Request $r):Response{$p=$this->products->find($this->id($r));return $p===null?$this->missing():$this->form($p,[],'Edit product','/admin/products/'.$p['id']);}
    public function update(Request $r):Response{$id=$this->id($r);if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();if($this->products->find($id)===null)return $this->missing();$v=$this->service->validate($r->input,$id);if(!$v->isValid())return $this->form($v->data,$v->errors,'Edit product','/admin/products/'.$id,422);$this->service->update($id,$v->data);$this->flash->success('Product updated successfully.');return Response::redirect('/admin/products/'.$id.'/edit');}
    public function status(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$this->products->setStatus($this->id($r),($r->input['is_active']??'')==='1');$this->flash->success('Product status changed.');return Response::redirect('/admin/products');}
    public function delete(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();if($this->products->delete($this->id($r)))$this->flash->success('Product deleted successfully.');else $this->flash->error('Product was not found.');return Response::redirect('/admin/products');}
    private function form(array $values,array $errors,string $title,string $action,int $status=200):Response{return $this->page->render('admin/products/form',$title,['values'=>$values,'errors'=>$errors,'action'=>$action,'brands'=>$this->brands->all(),'categories'=>$this->categories->all()],$status);}private function id(Request $r):int{return(int)($r->attributes['id']??0);}private function missing():Response{return $this->page->render('errors/404','Product not found',[],404);}private function positiveInt(mixed $v):int{$x=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return$x===false?0:$x;}private function bounded(mixed $v,int $max):string{$v=is_string($v)?trim($v):'';return strlen($v)<=$max?$v:substr($v,0,$max);}
}
