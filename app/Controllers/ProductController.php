<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductImageRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductSpecificationRepository;
use App\Services\ImageProcessingException;
use App\Services\ProductImageService;
use App\Services\ProductService;
use App\Services\ProductSpecificationService;
use App\Support\AdminPage;
use App\Support\Flash;

final readonly class ProductController
{
    public function __construct(private ProductRepository $products,private BrandRepository $brands,private CategoryRepository $categories,private ProductImageRepository $images,private ProductSpecificationRepository $specifications,private ProductService $service,private ProductImageService $imageService,private ProductSpecificationService $specificationService,private AdminPage $page,private Flash $flash) {}

    public function index(Request $request): Response
    {
        $filters=['q'=>$this->bounded($request->query['q']??'',100),'brand_id'=>$this->positiveInt($request->query['brand_id']??null),'category_id'=>$this->positiveInt($request->query['category_id']??null),'status'=>in_array($request->query['status']??'',['active','hidden'],true)?$request->query['status']:''];
        $page=max(1,$this->positiveInt($request->query['page']??1)); $result=$this->products->paginate($filters,$page);
        if ($page>$result['pages']) return Response::redirect('/admin/products');
        return $this->page->render('admin/products/index','Products',['result'=>$result,'filters'=>$filters,'brands'=>$this->brands->all(),'categories'=>$this->categories->all()]);
    }

    public function create(Request $request): Response { return $this->form(['is_active'=>0,'is_featured'=>0],[],'Create product','/admin/products'); }
    public function store(Request $request): Response
    {
        if (!$this->csrf($request)) return $this->page->csrfFailure(); $validation=$this->service->validate($request->input);
        if (!$validation->isValid()) return $this->form($validation->data,$validation->errors,'Create product','/admin/products',422);
        $id=$this->service->create($validation->data); $this->flash->success('Proizvod je dodat. Sada možete dodati slike i specifikacije.');
        return Response::redirect('/admin/products/'.$id.'/edit');
    }
    public function edit(Request $request): Response { $product=$this->products->find($this->id($request)); return $product===null?$this->missing():$this->form($product,[],'Edit product','/admin/products/'.$product['id']); }
    public function update(Request $request): Response
    {
        $id=$this->id($request); if (!$this->csrf($request)) return $this->page->csrfFailure(); if ($this->products->find($id)===null) return $this->missing();
        $validation=$this->service->validate($request->input,$id); if (!$validation->isValid()) return $this->form($validation->data,$validation->errors,'Edit product','/admin/products/'.$id,422);
        $this->service->update($id,$validation->data); $this->flash->success('Podaci o proizvodu su sačuvani.'); return $this->editRedirect($id);
    }
    public function saveSpecifications(Request $request): Response
    {
        $id=$this->id($request); if (!$this->csrf($request)) return $this->page->csrfFailure(); if ($this->products->find($id)===null) return $this->missing();
        $validation=$this->specificationService->validate($request->input);
        if (!$validation->isValid()) { $this->flash->error((string)reset($validation->errors)); return $this->editRedirect($id); }
        $this->specificationService->replace($id,$validation->data); $this->flash->success('Specifikacije su sačuvane.'); return $this->editRedirect($id);
    }
    public function uploadImages(Request $request): Response
    {
        $id=$this->id($request); if (!$this->csrf($request)) return $this->page->csrfFailure(); $product=$this->products->find($id); if ($product===null) return $this->missing();
        try { $count=$this->imageService->upload($id,(string)$product['name'],is_array($request->files['images']??null)?$request->files['images']:[]); $this->flash->success($count.' slika je uspešno dodato.'); }
        catch (ImageProcessingException $exception) { $this->flash->error($exception->getMessage()); }
        return $this->editRedirect($id);
    }
    public function mainImage(Request $request): Response
    {
        $id=$this->id($request); if (!$this->csrf($request)) return $this->page->csrfFailure(); if ($this->products->find($id)===null) return $this->missing();
        $this->imageService->setMain($id,$this->imageId($request))?$this->flash->success('Glavna slika je promenjena.'):$this->flash->error('Slika ne pripada ovom proizvodu.'); return $this->editRedirect($id);
    }
    public function deleteImage(Request $request): Response
    {
        $id=$this->id($request); if (!$this->csrf($request)) return $this->page->csrfFailure(); if ($this->products->find($id)===null) return $this->missing();
        $this->imageService->delete($id,$this->imageId($request))?$this->flash->success('Slika je obrisana.'):$this->flash->error('Slika ne pripada ovom proizvodu.'); return $this->editRedirect($id);
    }
    public function orderImages(Request $request): Response
    {
        $id=$this->id($request); if (!$this->csrf($request)) return $this->page->csrfFailure(); if ($this->products->find($id)===null) return $this->missing();
        $ids=is_array($request->input['image_ids']??null)?$request->input['image_ids']:[];
        $this->imageService->reorder($id,$ids)?$this->flash->success('Redosled slika je sačuvan.'):$this->flash->error('Redosled slika nije važeći.'); return $this->editRedirect($id);
    }
    public function status(Request $request): Response { if (!$this->csrf($request)) return $this->page->csrfFailure(); $this->products->setStatus($this->id($request),($request->input['is_active']??'')==='1'); $this->flash->success('Status proizvoda je promenjen.'); return Response::redirect('/admin/products'); }
    public function delete(Request $request): Response { if (!$this->csrf($request)) return $this->page->csrfFailure(); $this->service->delete($this->id($request))?$this->flash->success('Proizvod je obrisan.'):$this->flash->error('Proizvod nije pronađen.'); return Response::redirect('/admin/products'); }

    private function form(array $values,array $errors,string $title,string $action,int $status=200): Response
    {
        $id=(int)($values['id']??0);
        return $this->page->render('admin/products/form',$title,['values'=>$values,'errors'=>$errors,'action'=>$action,'brands'=>$this->brands->all(),'categories'=>$this->categories->all(),'images'=>$id>0?$this->images->allForProduct($id):[],'specifications'=>$id>0?$this->specifications->allForProduct($id):[],'productId'=>$id],$status);
    }
    private function csrf(Request $request): bool { return $this->page->csrfValid($request->input['_csrf']??null); }
    private function id(Request $request): int { return (int)($request->attributes['id']??0); }
    private function imageId(Request $request): int { return (int)($request->attributes['imageId']??0); }
    private function editRedirect(int $id): Response { return Response::redirect('/admin/products/'.$id.'/edit'); }
    private function missing(): Response { return $this->page->render('errors/404','Product not found',[],404); }
    private function positiveInt(mixed $value): int { $result=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); return $result===false?0:$result; }
    private function bounded(mixed $value,int $max): string { $value=is_string($value)?trim($value):''; return strlen($value)<=$max?$value:substr($value,0,$max); }
}
