<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\TailorAssignment;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TailoringOrderService
{
    public function __construct(
        private readonly StockMovementService $stockService,
        private readonly AccountingService    $accountingService,
    ) {}

    /**
     * Create a tailoring order with its garment line items and (for in-house
     * fabric) reserved materials. No stock is deducted yet — that happens
     * when an item enters the cutting stage.
     *
     * $data = [
     *   'customer_id'?, 'new_customer'? => [...customer fields], // one of the two is required
     *   'order_type', 'expected_delivery_date', 'notes',
     *   'items' => [
     *     [
     *       'garment_type', 'fabric_source', 'product_id'?, 'quantity', 'unit_price',
     *       'discount'?, 'style_specifications'?,
     *       'materials' => [['product_id', 'quantity_required'], ...] // when fabric_source = in_house
     *     ], ...
     *   ],
     *   'initial_payment'? => ['amount', 'payment_method'?, 'payment_type'],
     * ]
     */
    public function createOrder(array $data, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $customerId = $data['customer_id'] ?? null;

            if (!$customerId && !empty($data['new_customer'])) {
                $customerId = Customer::create($data['new_customer'])->id;
            }

            $subtotal = 0;

            foreach ($data['items'] as $itemData) {
                $qty      = (int) $itemData['quantity'];
                $lineTotal = ((float) $itemData['unit_price'] * $qty) - (float) ($itemData['discount'] ?? 0);
                $subtotal += $lineTotal;

                if (($itemData['fabric_source'] ?? null) === 'in_house') {
                    foreach ($itemData['materials'] ?? [] as $material) {
                        $product = Product::findOrFail($material['product_id']);
                        if ((float) $product->stock_quantity < (float) $material['quantity_required']) {
                            throw new RuntimeException(
                                "Insufficient stock for \"{$product->name}\". Available: {$product->stock_quantity}, required: {$material['quantity_required']}."
                            );
                        }
                    }
                }
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0);
            $taxAmount      = (float) ($data['tax_amount'] ?? 0);
            $totalAmount    = $subtotal - $discountAmount + $taxAmount;

            $order = Order::create([
                'customer_id'             => $customerId,
                'order_type'              => $data['order_type'],
                'status'                  => 'pending',
                'subtotal'                => $subtotal,
                'discount_amount'         => $discountAmount,
                'tax_amount'              => $taxAmount,
                'total_amount'            => $totalAmount,
                'deposit_amount'          => 0,
                'expected_delivery_date'  => $data['expected_delivery_date'] ?? null,
                'payment_method'          => $data['payment_method'] ?? 'cash',
                'payment_status'          => 'pending',
                'notes'                   => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $qty      = (int) $itemData['quantity'];
                $discount = (float) ($itemData['discount'] ?? 0);
                $lineTotal = ((float) $itemData['unit_price'] * $qty) - $discount;

                /** @var OrderItem $item */
                $item = $order->items()->create([
                    'product_id'            => $itemData['product_id'] ?? null,
                    'product_name'          => $itemData['garment_type'],
                    'garment_type'          => $itemData['garment_type'],
                    'fabric_source'         => $itemData['fabric_source'],
                    'style_specifications'  => $itemData['style_specifications'] ?? null,
                    'production_status'     => 'pending',
                    'unit_price'            => $itemData['unit_price'],
                    'quantity'              => $qty,
                    'discount'              => $discount,
                    'total'                 => $lineTotal,
                ]);

                if ($itemData['fabric_source'] === 'in_house') {
                    foreach ($itemData['materials'] ?? [] as $material) {
                        $item->materials()->create([
                            'product_id'        => $material['product_id'],
                            'quantity_required' => $material['quantity_required'],
                            'status'            => 'reserved',
                        ]);
                    }
                }
            }

            if (!empty($data['initial_payment']['amount'])) {
                $this->recordPayment($order, $data['initial_payment'], $userId);
            }

            return $order->load(['customer', 'items.materials', 'payments']);
        });
    }

    /**
     * Record a payment against an order. Cash received before delivery is
     * held as Unearned Revenue and recognised as Sales Revenue on completion.
     */
    public function recordPayment(Order $order, array $data, ?int $userId): OrderPayment
    {
        return DB::transaction(function () use ($order, $data, $userId) {
            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }
            if ($amount > $order->balance_due + 0.001) {
                throw new RuntimeException("Payment {$amount} exceeds the balance due ({$order->balance_due}).");
            }

            /** @var OrderPayment $payment */
            $payment = $order->payments()->create([
                'amount'                 => $amount,
                'payment_method'         => $data['payment_method'] ?? 'cash',
                'payment_type'           => $data['payment_type'],
                'transaction_reference'  => $data['transaction_reference'] ?? null,
                'paid_at'                => now(),
                'created_by'             => $userId,
            ]);

            if ($data['payment_type'] === 'deposit') {
                $order->increment('deposit_amount', $amount);
            }

            if (in_array($order->status, ['pending', 'quoted'])) {
                $order->update(['status' => 'deposit_paid']);
            }

            $order->update([
                'payment_status' => $order->fresh()->isFullyPaid() ? 'paid' : 'pending',
            ]);

            $this->accountingService->postEntry(
                description:   "Payment received for {$order->order_number}",
                lines: [
                    ['account' => AccountingService::CASH,             'debit'  => $amount],
                    ['account' => AccountingService::UNEARNED_REVENUE, 'credit' => $amount],
                ],
                referenceType: 'order',
                referenceId:   $order->id,
                createdBy:     $userId,
            );

            return $payment;
        });
    }

    /**
     * Move a garment line item to a new production stage. Entering "cutting"
     * consumes any reserved in-house materials: deducts stock and posts the
     * WIP/Inventory accounting entry.
     */
    public function advanceProductionStatus(OrderItem $item, string $newStatus, ?int $userId): OrderItem
    {
        return DB::transaction(function () use ($item, $newStatus, $userId) {
            if ($newStatus === 'cutting') {
                $consumedValue = 0.0;

                foreach ($item->materials()->where('status', 'reserved')->get() as $material) {
                    $product = Product::findOrFail($material->product_id);

                    $this->stockService->record(
                        productId:     $product->id,
                        variantId:     null,
                        type:          'material_consumed',
                        quantity:      (float) $material->quantity_required,
                        costPrice:     (float) $product->cost_price,
                        referenceType: 'order_item',
                        referenceId:   $item->id,
                        notes:         "Cut for job card {$item->job_card_number}",
                        createdBy:     $userId,
                    );

                    $material->update(['status' => 'consumed', 'consumed_at' => now()]);
                    $consumedValue += (float) $product->cost_price * (float) $material->quantity_required;
                }

                if ($consumedValue > 0) {
                    $this->accountingService->postEntry(
                        description:   "Materials cut for job card {$item->job_card_number}",
                        lines: [
                            ['account' => AccountingService::WIP,              'debit'  => $consumedValue],
                            ['account' => AccountingService::INVENTORY_ASSET,  'credit' => $consumedValue],
                        ],
                        referenceType: 'order_item',
                        referenceId:   $item->id,
                        createdBy:     $userId,
                    );
                }
            }

            $item->update(['production_status' => $newStatus]);

            $this->syncOrderStatus($item->order);

            return $item->fresh(['materials', 'assignments.tailor']);
        });
    }

    public function assignTailor(OrderItem $item, int $tailorId, ?string $role): TailorAssignment
    {
        return $item->assignments()->create([
            'tailor_id'     => $tailorId,
            'assigned_role' => $role,
            'assigned_at'   => now(),
        ]);
    }

    public function recordQc(OrderItem $item, bool $passed, ?string $notes): OrderItem
    {
        $item->update([
            'production_status' => $passed ? 'ready' : 'rework',
            'qc_notes'           => $notes,
            'qc_passed_at'       => $passed ? now() : null,
        ]);

        $this->syncOrderStatus($item->order);

        return $item->fresh();
    }

    /**
     * Finalise the order: validate it is fully paid, recognise revenue and
     * cost of goods sold, and mark all items delivered.
     */
    public function completeOrder(Order $order, ?int $userId): Order
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = Order::with('items.materials.product')->lockForUpdate()->findOrFail($order->id);

            if (!$order->isFullyPaid()) {
                throw new RuntimeException("Order {$order->order_number} is not fully paid (balance due: {$order->balance_due}).");
            }

            $consumedValue = 0.0;
            foreach ($order->items as $item) {
                foreach ($item->materials->where('status', 'consumed') as $material) {
                    $consumedValue += (float) ($material->product->cost_price ?? 0) * (float) $material->quantity_required;
                }
            }

            $this->accountingService->postEntry(
                description:   "Order delivered: {$order->order_number}",
                lines: array_values(array_filter([
                    ['account' => AccountingService::UNEARNED_REVENUE, 'debit'  => (float) $order->total_amount],
                    ['account' => AccountingService::SALES_REVENUE,    'credit' => (float) $order->total_amount],
                    $consumedValue > 0 ? ['account' => AccountingService::COGS, 'debit'  => $consumedValue] : null,
                    $consumedValue > 0 ? ['account' => AccountingService::WIP,  'credit' => $consumedValue] : null,
                ])),
                referenceType: 'order',
                referenceId:   $order->id,
                createdBy:     $userId,
            );

            $order->update(['status' => 'completed', 'payment_status' => 'paid']);
            $order->items()->update(['production_status' => 'delivered']);

            $this->notifyCustomer(
                $order,
                'Your order has been delivered',
                'Thank you! Your order has been completed and delivered. We appreciate your business.',
            );

            return $order->fresh(['customer', 'items']);
        });
    }

    /**
     * Manually (re)send a status-update email to the customer, e.g. from the
     * order detail page's "Send Update Email" action.
     */
    public function sendManualNotification(Order $order, ?string $message): void
    {
        $body = $message ?: "Here's an update on your order {$order->order_number} (status: {$order->status}).";

        $this->notifyCustomer($order, 'Order Update', $body);
    }

    /**
     * Dispatch an order-status email to the customer. Failures are logged but
     * never allowed to roll back the surrounding order transaction.
     */
    private function notifyCustomer(Order $order, string $headline, string $body): void
    {
        try {
            $order->loadMissing('customer');
            $order->customer?->notify(new OrderStatusNotification($order, $headline, $body));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Keep the parent order's status roughly in sync with its items'
     * production progress (informational — item statuses remain the source
     * of truth for the production board).
     */
    private function syncOrderStatus(Order $order): void
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return;
        }

        $statuses = $order->items()->pluck('production_status');

        if ($statuses->isEmpty()) {
            return;
        }

        $previousStatus = $order->status;

        if ($statuses->every(fn ($s) => $s === 'ready')) {
            $order->update(['status' => 'ready_for_fitting']);

            if ($previousStatus !== 'ready_for_fitting') {
                $this->notifyCustomer(
                    $order,
                    'Your order is ready',
                    'Great news! Your garment(s) are ready — please visit us for a fitting.',
                );
            }
        } elseif ($statuses->contains(fn ($s) => !in_array($s, ['pending']))) {
            $order->update(['status' => 'in_production']);
        }
    }
}
