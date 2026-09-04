<?php

namespace App\Providers;

use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\BrandRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(BrandRepositoryInterface::class, BrandRepository::class);
    }
}
