<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Inventory;
use App\Models\Sale;
use App\Observers\InventoryObserver;
use App\Observers\SaleObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Inventory::observe(InventoryObserver::class);
        Sale::observe(SaleObserver::class);
    }
}