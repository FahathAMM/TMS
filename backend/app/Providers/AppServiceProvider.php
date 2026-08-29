<?php

namespace App\Providers;

use App\Models\AlterationOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SupplierReturn;
use App\Notifications\Channels\LoggedSmsChannel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Notification;
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
            'alteration_order' => AlterationOrder::class,
            // 'adjustment' intentionally omitted — no model, reference_id is null
        ]);

        // Placeholder WhatsApp/SMS channel — logs instead of calling a real
        // gateway until Twilio/WhatsApp Business API credentials exist. Not
        // used by default; a notification opts in by adding 'sms' to via().
        Notification::extend('sms', fn ($app) => $app->make(LoggedSmsChannel::class));
    }
}
