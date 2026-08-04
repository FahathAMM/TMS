<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Map short alias strings stored in stock_movements.reference_type /
        // journal_entries.reference_type to their Eloquent models so
        // morphTo() resolves correctly.
        Relation::morphMap([
            'purchase'        => Purchase::class,
            'sale'            => Sale::class,
            'supplier_return' => SupplierReturn::class,
            'sale_return'     => SaleReturn::class,
            'order'           => Order::class,
            'order_item'      => OrderItem::class,
            // 'adjustment' intentionally omitted — no model, reference_id is null
        ]);
    }
}
