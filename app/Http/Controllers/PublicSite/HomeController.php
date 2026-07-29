<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __construct(
        private readonly PublicHomepageReadService $homepage,
        private readonly WebsitePagesReadService $pages,
        private readonly SeoReadService $seo,
    ) {}

    public function index(): View
    {
        return view('public.home', $this->homepage->payload() + [
            'homePage' => $this->pages->getPage('home'),
            'seo' => $this->seo->forKey('home', url('/')),
            'testimonySourcePage' => 'index',
        ]);
    }
}
