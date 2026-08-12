<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;use App\Http\Response;use App\Repositories\BrandRepository;use App\Services\BrandService;use App\Support\AdminPage;use App\Support\Flash;

final readonly class BrandController
{
    public function __construct(private BrandRepository $brands,private BrandService $service,private AdminPage $page,private Flash $flash){}
    public function index(Request $r):Response{return $this->page->render('admin/brands/index','Brands',['brands'=>$this->brands->allWithProductCounts()]);}
    public function create(Request $r):Response{return $this->form([],[],'Create brand','/admin/brands');}
    public function store(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$v=$this->service->validate($r->input);if(!$v->isValid())return $this->form($v->data,$v->errors,'Create brand','/admin/brands',422);$this->service->create($v->data);$this->flash->success('Brand created successfully.');return Response::redirect('/admin/brands');}
    public function edit(Request $r):Response{$brand=$this->brands->find($this->id($r));return $brand===null?$this->missing():$this->form($brand,[],'Edit brand','/admin/brands/'.$brand['id']);}
    public function update(Request $r):Response{$id=$this->id($r);if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();if($this->brands->find($id)===null)return $this->missing();$v=$this->service->validate($r->input,$id);if(!$v->isValid())return $this->form($v->data,$v->errors,'Edit brand','/admin/brands/'.$id,422);$this->service->update($id,$v->data);$this->flash->success('Brand updated successfully.');return Response::redirect('/admin/brands');}
    public function status(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$active=($r->input['is_active']??'')==='1';$this->brands->setStatus($this->id($r),$active);$this->flash->success('Brand status changed.');return Response::redirect('/admin/brands');}
    public function delete(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$result=$this->service->delete($this->id($r));if($result==='referenced')$this->flash->error('Brand cannot be deleted because products are assigned to it.');elseif($result==='deleted')$this->flash->success('Brand deleted successfully.');else $this->flash->error('Brand was not found.');return Response::redirect('/admin/brands');}
    private function form(array $values,array $errors,string $title,string $action,int $status=200):Response{return $this->page->render('admin/brands/form',$title,['values'=>$values,'errors'=>$errors,'action'=>$action],$status);}private function id(Request $r):int{return (int)($r->attributes['id']??0);}private function missing():Response{return $this->page->render('errors/404','Brand not found',[],404);}
}
