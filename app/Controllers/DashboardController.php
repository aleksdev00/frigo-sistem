<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;use App\Http\Response;use App\Repositories\BrandRepository;use App\Repositories\CategoryRepository;use App\Repositories\ProductRepository;use App\Services\AuthService;use App\Support\AdminPage;

final readonly class DashboardController
{
    public function __construct(private ProductRepository $products,private BrandRepository $brands,private CategoryRepository $categories,private AuthService $auth,private AdminPage $page){}
    public function index(Request $r):Response{return $this->page->render('admin/dashboard','Dashboard',['counts'=>$this->products->counts(),'brandCount'=>$this->brands->count(),'categoryCount'=>$this->categories->count(),'recent'=>$this->products->recent(),'username'=>$this->auth->currentUsername()]);}
}
