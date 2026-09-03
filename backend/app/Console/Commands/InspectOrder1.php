<?php

namespace App\Console\Commands;

use App\Models\Tailoring\Order;
use Illuminate\Console\Command;

class InspectOrder1 extends Command
{
    protected $signature = 'app:inspect-order1';
    protected $description = 'Debug: inspect order 1 and its items';

    public function handle(): void
    {
        $order = Order::with('items.assignments.tailor', 'items.materials')->find(1);
        $this->info("Order status: {$order->status}, payment_status: {$order->payment_status}");
        foreach ($order->items as $item) {
            $this->info("Item #{$item->id}: {$item->garment_type} production_status={$item->production_status} updated_at={$item->updated_at}");
            foreach ($item->assignments as $a) {
                $this->line("  assignment: tailor={$a->tailor?->full_name} completed_at={$a->completed_at}");
            }
        }
    }
}
