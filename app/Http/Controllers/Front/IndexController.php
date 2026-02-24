<?php

namespace App\Http\Controllers\Front;

use App\Services\Front\IndexService;
use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    protected $indexService;

    public function __construct(IndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    public function index()
    {
        $banners = $this->indexService->getHomePageBanners();
        $featured = $this->indexService->featuredProducts();
        $newArrivals = $this->indexService->newArrivalProducts();
        $categories = $this->indexService->homeCategories();
    return view('front.index')
            ->with($banners)
            ->with($featured)
            ->with($newArrivals)
            ->with($categories);
    }
}
