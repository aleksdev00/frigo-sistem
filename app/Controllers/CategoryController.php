<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;use App\Http\Response;use App\Repositories\CategoryRepository;use App\Services\CategoryService;use App\Support\AdminPage;use App\Support\Flash;

final readonly class CategoryController
{
    public function __construct(private CategoryRepository $categories,private CategoryService $service,private AdminPage $page,private Flash $flash){}
    public function index(Request $r):Response{return $this->page->render('admin/categories/index','Categories',['categories'=>$this->categories->allWithProductCounts()]);}
    public function create(Request $r):Response{return $this->form([],[],'Create category','/admin/categories');}
    public function store(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$v=$this->service->validate($r->input);if(!$v->isValid())return $this->form($v->data,$v->errors,'Create category','/admin/categories',422);$this->service->create($v->data);$this->flash->success('Category created successfully.');return Response::redirect('/admin/categories');}
    public function edit(Request $r):Response{$item=$this->categories->find($this->id($r));return $item===null?$this->missing():$this->form($item,[],'Edit category','/admin/categories/'.$item['id']);}
    public function update(Request $r):Response{$id=$this->id($r);if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();if($this->categories->find($id)===null)return $this->missing();$v=$this->service->validate($r->input,$id);if(!$v->isValid())return $this->form($v->data,$v->errors,'Edit category','/admin/categories/'.$id,422);$this->service->update($id,$v->data);$this->flash->success('Category updated successfully.');return Response::redirect('/admin/categories');}
    public function status(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$this->categories->setStatus($this->id($r),($r->input['is_active']??'')==='1');$this->flash->success('Category status changed.');return Response::redirect('/admin/categories');}
    public function delete(Request $r):Response{if(!$this->page->csrfValid($r->input['_csrf']??null))return $this->page->csrfFailure();$result=$this->service->delete($this->id($r));if($result==='referenced')$this->flash->error('Category cannot be deleted because products are assigned to it.');elseif($result==='deleted')$this->flash->success('Category deleted successfully.');else $this->flash->error('Category was not found.');return Response::redirect('/admin/categories');}
    private function form(array $values,array $errors,string $title,string $action,int $status=200):Response{return $this->page->render('admin/categories/form',$title,['values'=>$values,'errors'=>$errors,'action'=>$action],$status);}private function id(Request $r):int{return(int)($r->attributes['id']??0);}private function missing():Response{return $this->page->render('errors/404','Category not found',[],404);}
}
