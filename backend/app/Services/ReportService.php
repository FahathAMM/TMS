<?php

namespace App\Services;

use App\Models\AlterationGarment;
use App\Models\AlterationOrder;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\TailorAssignment;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Order counts and totals grouped by status, within an optional date range.
     */
    public function ordersSummary(?string $from, ?string $to): array
    {
        $query = Order::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count'  => (int) $row->count,
                'total'  => round((float) $row->total, 2),
            ])->all();

        return [
            'by_status'    => $byStatus,
            'total_orders' => (clone $query)->count(),
            'total_value'  => round((float) (clone $query)->sum('total_amount'), 2),
        ];
    }

    /**
     * Payments collected grouped by date and payment method, within an optional range.
     */
    public function paymentsCollected(?string $from, ?string $to): array
    {
        $query = OrderPayment::query()
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to));

        $byDate = (clone $query)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'total' => round((float) $row->total, 2)])->all();

        $byMethod = (clone $query)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => ['payment_method' => $row->payment_method, 'total' => round((float) $row->total, 2)])->all();

        return [
            'by_date'   => $byDate,
            'by_method' => $byMethod,
            'total'     => round((float) (clone $query)->sum('amount'), 2),
        ];
    }

    /**
     * Orders with a balance still owing.
     */
    public function outstandingBalances(): array
    {
        return Order::with('customer')
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->map(fn (Order $order) => [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'customer'     => $order->customer?->name,
                'total_amount' => (float) $order->total_amount,
                'paid_amount'  => $order->paid_amount,
                'balance_due'  => $order->balance_due,
                'status'       => $order->status,
            ])
            ->filter(fn ($row) => $row['balance_due'] > 0)
            ->sortByDesc('balance_due')
            ->values()
            ->all();
    }

    /**
     * Current stock level per active fabric/trim product, flagged when low.
     */
    public function stockSummary(): array
    {
        return Product::where('is_active', true)
            ->select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold', 'unit_of_measure')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id'                  => $product->id,
                'name'                => $product->name,
                'sku'                 => $product->sku,
                'stock_quantity'      => (float) $product->stock_quantity,
                'low_stock_threshold' => (float) $product->low_stock_threshold,
                'unit_of_measure'     => $product->unit_of_measure,
                'is_low_stock'        => $product->isLowStock(),
            ])->all();
    }

    /**
     * Purchase order totals grouped by supplier, within an optional date range.
     */
    public function purchasesSummary(?string $from, ?string $to): array
    {
        return Purchase::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->when($from, fn ($q) => $q->whereDate('purchases.purchase_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('purchases.purchase_date', '<=', $to))
            ->select('suppliers.name as supplier', DB::raw('COUNT(*) as count'), DB::raw('SUM(purchases.total_amount) as total'))
            ->groupBy('suppliers.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'supplier' => $row->supplier,
                'count'    => (int) $row->count,
                'total'    => round((float) $row->total, 2),
            ])->all();
    }

    /**
     * Expense totals grouped by category, within an optional date range.
     */
    public function expensesSummary(?string $from, ?string $to): array
    {
        return Expense::query()
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->select('category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'count'    => (int) $row->count,
                'total'    => round((float) $row->total, 2),
            ])->all();
    }

    /**
     * Completed garment assignments per tailor, within an optional date range.
     */
    public function tailorProductivity(?string $from, ?string $to): array
    {
        return TailorAssignment::query()
            ->join('tailors', 'tailors.id', '=', 'tailor_assignments.tailor_id')
            ->whereNotNull('tailor_assignments.completed_at')
            ->when($from, fn ($q) => $q->whereDate('tailor_assignments.completed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('tailor_assignments.completed_at', '<=', $to))
            ->select(
                'tailors.id as tailor_id',
                DB::raw("CONCAT(tailors.first_name, ' ', tailors.last_name) as tailor_name"),
                DB::raw('COUNT(*) as items_completed'),
            )
            ->groupBy('tailors.id', 'tailors.first_name', 'tailors.last_name')
            ->orderByDesc('items_completed')
            ->get()
            ->map(fn ($row) => [
                'tailor_id'        => $row->tailor_id,
                'tailor_name'      => $row->tailor_name,
                'items_completed'  => (int) $row->items_completed,
            ])->all();
    }

    /**
     * Alteration order counts and totals grouped by status, plus how many
     * garments are ready and waiting for pickup right now.
     */
    public function alterationOrdersSummary(?string $from, ?string $to): array
    {
        $query = AlterationOrder::query()
            ->when($from, fn ($q) => $q->whereDate('received_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('received_date', '<=', $to));

        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count'  => (int) $row->count,
                'total'  => round((float) $row->total, 2),
            ])->all();

        return [
            'by_status'               => $byStatus,
            'total_orders'            => (clone $query)->count(),
            'total_value'             => round((float) (clone $query)->sum('total_amount'), 2),
            'garments_pending_pickup' => AlterationGarment::where('status', 'ready')->count(),
        ];
    }

    /**
     * Recognised alteration revenue (from the GL), within an optional date range.
     */
    public function alterationRevenue(?string $from, ?string $to): array
    {
        $lineQuery = fn () => DB::table('journal_entry_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('accounts.code', AccountingService::ALTERATION_REVENUE)
            ->when($from, fn ($q) => $q->where('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('journal_entries.entry_date', '<=', $to));

        $totalRevenue = (float) $lineQuery()->sum('credit') - (float) $lineQuery()->sum('debit');

        $byDate = $lineQuery()
            ->select(
                'journal_entries.entry_date as date',
                DB::raw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) as total'),
            )
            ->groupBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'total' => round((float) $row->total, 2)])
            ->all();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'by_date'       => $byDate,
        ];
    }
}
