<?php

namespace App\Services;

use App\Models\Tailoring\AlterationGarment;
use App\Models\Tailoring\AlterationGarmentPhoto;
use App\Models\Tailoring\AlterationOrder;
use App\Models\Tailoring\AlterationOrderPayment;
use App\Models\Tailoring\AlterationStatusHistory;
use App\Models\Tailoring\AlterationTask;
use App\Models\Tailoring\AlterationTaskAssignment;
use App\Models\Tailoring\AlterationType;
use App\Models\Customers\Customer;
use App\Notifications\AlterationOrderNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class AlterationOrderService
{
    public function __construct(private readonly AccountingService $accountingService) {}

    /**
     * Receive one or more garments (each with one or more alteration tasks,
     * and optional measurements) as a new alteration order.
     *
     * $data = [
     *   'customer_id'?, 'new_customer'? => [...],
     *   'priority'?, 'promised_date'?, 'notes'?, 'discount_amount'?, 'tax_amount'?,
     *   'garments' => [
     *     [
     *       'garment_type', 'description'?, 'quantity'?, 'measurements_required'?, 'notes'?,
     *       'tasks' => [['alteration_type_id'?, 'description'?, 'price'?, 'quantity'?, 'notes'?], ...],
     *       'measurements' => [['measurement_field_id', 'current_value'?, 'target_value'?], ...],
     *     ], ...
     *   ],
     *   'initial_payment'? => ['amount', 'payment_method'?, 'payment_type'],
     * ]
     */
    public function createOrder(array $data, ?int $userId): AlterationOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $customerId = $data['customer_id'] ?? null;

            if (!$customerId && !empty($data['new_customer'])) {
                $customerId = Customer::create($data['new_customer'])->id;
            }

            /** @var AlterationOrder $order */
            $order = AlterationOrder::create([
                'customer_id'     => $customerId,
                'status'          => 'received',
                'priority'        => $data['priority'] ?? 'normal',
                'received_date'   => now()->toDateString(),
                'promised_date'   => $data['promised_date'] ?? null,
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'tax_amount'      => (float) ($data['tax_amount'] ?? 0),
                'notes'           => $data['notes'] ?? null,
                'received_by'     => $userId,
                'created_by'      => $userId,
            ]);

            foreach ($data['garments'] as $garmentData) {
                $this->createGarmentInternal($order, $garmentData);
            }

            $this->recalculateOrderTotals($order);
            $this->logHistory($order, null, null, null, 'received', $userId, 'Order received');

            if (!empty($data['initial_payment']['amount'])) {
                $this->recordPayment($order, $data['initial_payment'], $userId);
            }

            return $order->fresh(['customer', 'garments.tasks.alterationType', 'garments.photos', 'garments.measurements.measurementField', 'payments']);
        });
    }

    /**
     * Receive an additional garment on an already-open order (customer comes
     * back with one more item, or staff forgot to log one at intake).
     */
    public function addGarment(AlterationOrder $order, array $data, ?int $userId): AlterationGarment
    {
        return DB::transaction(function () use ($order, $data, $userId) {
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                throw new RuntimeException("Cannot add a garment to a {$order->status} order.");
            }

            $garment = $this->createGarmentInternal($order, $data);
            $this->recalculateOrderTotals($order);
            $this->logHistory($order, $garment, null, null, 'pending', $userId, 'Garment added to existing order');
            $this->syncOrderStatus($order->fresh());

            return $garment->fresh(['tasks.alterationType', 'photos', 'measurements.measurementField']);
        });
    }

    /**
     * Add another task to a garment already in the shop (an extra fix the
     * customer or staff spots after intake).
     */
    public function addTask(AlterationGarment $garment, array $data, ?int $userId): AlterationTask
    {
        return DB::transaction(function () use ($garment, $data, $userId) {
            $task = $this->createTaskInternal($garment, $data);
            $this->recalculateOrderTotals($garment->order()->firstOrFail());
            $this->syncGarmentStatus($garment->fresh());
            $this->syncOrderStatus($garment->order()->firstOrFail());

            return $task;
        });
    }

    public function assignTailor(AlterationTask $task, int $tailorId): AlterationTaskAssignment
    {
        return $task->assignments()->create([
            'tailor_id'   => $tailorId,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Move a task through pending → in_progress → completed. Cascades a
     * garment-status re-sync, then an order-status re-sync, logging every
     * transition to the audit trail.
     */
    public function advanceTaskStatus(AlterationTask $task, string $newStatus, ?int $userId, ?string $notes = null): AlterationTask
    {
        return DB::transaction(function () use ($task, $newStatus, $userId, $notes) {
            $from = $task->status;

            $updates = ['status' => $newStatus];
            if ($newStatus === 'in_progress' && !$task->started_at) {
                $updates['started_at'] = now();
            }
            if ($newStatus === 'completed') {
                $updates['completed_at'] = now();
            }
            $task->update($updates);

            if ($newStatus === 'completed') {
                $task->assignments()->whereNull('completed_at')->latest('assigned_at')->first()?->update(['completed_at' => now()]);
            }

            $garment = $task->garment()->firstOrFail();
            $this->logHistory($garment->order()->firstOrFail(), $garment, $task, $from, $newStatus, $userId, $notes);

            $this->syncGarmentStatus($garment);
            $this->syncOrderStatus($garment->order()->firstOrFail());

            return $task->fresh(['assignments.tailor']);
        });
    }

    /**
     * Physically hand a finished garment back to the customer. Independent
     * of payment — some shops collect balance at handover, others don't;
     * that reconciliation happens in completeOrder().
     */
    public function markGarmentDelivered(AlterationGarment $garment, ?int $userId): AlterationGarment
    {
        return DB::transaction(function () use ($garment, $userId) {
            if ($garment->status === 'delivered') {
                throw new RuntimeException('Garment has already been delivered.');
            }

            $from = $garment->status;
            $garment->update(['status' => 'delivered', 'delivered_at' => now()]);

            $order = $garment->order()->firstOrFail();
            $this->logHistory($order, $garment, null, $from, 'delivered', $userId, 'Handed over to customer');

            if ($order->garments()->where('status', '!=', 'delivered')->doesntExist()) {
                $prevStatus = $order->status;
                $order->update(['status' => 'delivered', 'delivered_date' => now()->toDateString()]);
                $this->logHistory($order, null, null, $prevStatus, 'delivered', $userId, 'All garments delivered');
                $this->notifyCustomer($order, 'Order delivered', "Thank you! All items on order {$order->order_number} have been delivered.");
            }

            return $garment->fresh();
        });
    }

    /**
     * Record a payment against the order. Cash received before delivery is
     * held as Unearned Revenue until completeOrder() recognises it.
     */
    public function recordPayment(AlterationOrder $order, array $data, ?int $userId): AlterationOrderPayment
    {
        return DB::transaction(function () use ($order, $data, $userId) {
            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }
            if ($amount > $order->balance_due + 0.001) {
                throw new RuntimeException("Payment {$amount} exceeds the balance due ({$order->balance_due}).");
            }

            /** @var AlterationOrderPayment $payment */
            $payment = $order->payments()->create([
                'amount'                => $amount,
                'payment_method'        => $data['payment_method'] ?? 'cash',
                'payment_type'          => $data['payment_type'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'paid_at'               => now(),
                'created_by'            => $userId,
            ]);

            $order->update([
                'payment_status' => $order->fresh()->isFullyPaid() ? 'paid' : 'partial',
            ]);

            $this->accountingService->postEntry(
                description:   "Payment received for {$order->order_number}",
                lines: [
                    ['account' => AccountingService::CASH,             'debit'  => $amount],
                    ['account' => AccountingService::UNEARNED_REVENUE, 'credit' => $amount],
                ],
                referenceType: 'alteration_order',
                referenceId:   $order->id,
                createdBy:     $userId,
            );

            return $payment;
        });
    }

    /**
     * Financially close the order: requires every garment delivered and the
     * balance fully settled, then recognises alteration revenue.
     */
    public function completeOrder(AlterationOrder $order, ?int $userId): AlterationOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = AlterationOrder::with('garments')->lockForUpdate()->findOrFail($order->id);

            if ($order->completed_at) {
                throw new RuntimeException("Order {$order->order_number} has already been completed.");
            }
            if ($order->garments()->where('status', '!=', 'delivered')->exists()) {
                throw new RuntimeException('All garments must be delivered before completing the order.');
            }
            if (!$order->isFullyPaid()) {
                throw new RuntimeException("Order {$order->order_number} is not fully paid (balance due: {$order->balance_due}).");
            }

            $this->accountingService->postEntry(
                description:   "Alteration order completed: {$order->order_number}",
                lines: [
                    ['account' => AccountingService::UNEARNED_REVENUE,   'debit'  => (float) $order->total_amount],
                    ['account' => AccountingService::ALTERATION_REVENUE, 'credit' => (float) $order->total_amount],
                ],
                referenceType: 'alteration_order',
                referenceId:   $order->id,
                createdBy:     $userId,
            );

            $order->update(['payment_status' => 'paid', 'completed_at' => now()]);

            return $order->fresh(['customer', 'garments.tasks']);
        });
    }

    public function cancelOrder(AlterationOrder $order, ?string $reason, ?int $userId): AlterationOrder
    {
        if ($order->status === 'delivered') {
            throw new RuntimeException('Cannot cancel an order that has already been delivered.');
        }

        $from = $order->status;
        $order->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => $reason]);
        $this->logHistory($order, null, null, $from, 'cancelled', $userId, $reason);

        return $order->fresh();
    }

    public function uploadGarmentPhoto(AlterationGarment $garment, UploadedFile $file, string $type, ?int $userId): AlterationGarmentPhoto
    {
        $path = $file->store("alterations/{$garment->alteration_order_id}/{$garment->id}/{$type}", 'public');

        return $garment->photos()->create([
            'type'        => $type,
            'path'        => $path,
            'uploaded_by' => $userId,
        ]);
    }

    public function sendManualNotification(AlterationOrder $order, ?string $message): void
    {
        $body = $message ?: "Here's an update on your alteration order {$order->order_number} (status: {$order->status}).";
        $this->notifyCustomer($order, 'Alteration Order Update', $body);
    }

    // ─── Internal helpers ───────────────────────────────────────────────────────

    private function createGarmentInternal(AlterationOrder $order, array $data): AlterationGarment
    {
        /** @var AlterationGarment $garment */
        $garment = $order->garments()->create([
            'garment_type'           => $data['garment_type'],
            'description'            => $data['description'] ?? null,
            'quantity'               => $data['quantity'] ?? 1,
            'status'                 => 'pending',
            'measurements_required'  => !empty($data['measurements_required']),
            'notes'                  => $data['notes'] ?? null,
        ]);

        foreach ($data['tasks'] ?? [] as $taskData) {
            $this->createTaskInternal($garment, $taskData);
        }

        foreach ($data['measurements'] ?? [] as $measurement) {
            $garment->measurements()->create([
                'measurement_field_id' => $measurement['measurement_field_id'],
                'current_value'        => $measurement['current_value'] ?? null,
                'target_value'         => $measurement['target_value'] ?? null,
            ]);
        }

        return $garment->load('tasks');
    }

    private function createTaskInternal(AlterationGarment $garment, array $data): AlterationTask
    {
        $alterationTypeId = $data['alteration_type_id'] ?? null;
        $price            = $data['price'] ?? null;
        $description      = $data['description'] ?? null;

        if ($alterationTypeId && $price === null) {
            $type = AlterationType::find($alterationTypeId);
            $price ??= (float) ($type->price ?? 0);
            $description = $description ?: $type?->name;
        }

        return $garment->tasks()->create([
            'alteration_type_id' => $alterationTypeId,
            'description'        => $description ?: 'Alteration',
            'price'              => $price ?? 0,
            'quantity'           => $data['quantity'] ?? 1,
            'status'             => 'pending',
            'notes'              => $data['notes'] ?? null,
        ]);
    }

    private function recalculateOrderTotals(AlterationOrder $order): void
    {
        $subtotal = AlterationTask::whereHas('garment', fn ($q) => $q->where('alteration_order_id', $order->id))
            ->get()
            ->sum(fn (AlterationTask $t) => $t->price * $t->quantity);

        $order->update([
            'subtotal'     => $subtotal,
            'total_amount' => $subtotal - $order->discount_amount + $order->tax_amount,
        ]);
    }

    /**
     * Garment status is derived from its tasks: all completed → ready; any
     * started → in_progress; otherwise pending. "delivered" is manual and
     * never auto-overwritten here.
     */
    private function syncGarmentStatus(AlterationGarment $garment): void
    {
        if ($garment->status === 'delivered') {
            return;
        }

        $statuses = $garment->tasks()->pluck('status');
        if ($statuses->isEmpty()) {
            return;
        }

        $previous = $garment->status;
        $new = match (true) {
            $statuses->every(fn ($s) => $s === 'completed') => 'ready',
            $statuses->contains(fn ($s) => $s !== 'pending') => 'in_progress',
            default => 'pending',
        };

        if ($new !== $previous) {
            $garment->update(['status' => $new]);
            $this->logHistory($garment->order()->firstOrFail(), $garment, null, $previous, $new, null, 'Auto-derived from task statuses');
        }
    }

    /**
     * Order status is derived from its garments: all delivered → delivered;
     * all ready/delivered → ready; any started → in_progress; otherwise
     * received. Cancelled/delivered are terminal and never auto-overwritten.
     */
    private function syncOrderStatus(AlterationOrder $order): void
    {
        if (in_array($order->status, ['cancelled', 'delivered'])) {
            return;
        }

        $statuses = $order->garments()->pluck('status');
        if ($statuses->isEmpty()) {
            return;
        }

        $previous = $order->status;
        $new = match (true) {
            $statuses->every(fn ($s) => $s === 'delivered') => 'delivered',
            $statuses->every(fn ($s) => in_array($s, ['ready', 'delivered'])) => 'ready',
            $statuses->contains(fn ($s) => $s !== 'pending') => 'in_progress',
            default => 'received',
        };

        if ($new !== $previous) {
            $order->update(array_filter([
                'status'         => $new,
                'delivered_date' => $new === 'delivered' ? now()->toDateString() : null,
            ]));
            $this->logHistory($order, null, null, $previous, $new, null, 'Auto-derived from garment statuses');

            if ($new === 'ready') {
                $this->notifyCustomer($order, 'Your alteration is ready', "Great news! Your garment(s) on order {$order->order_number} are ready for pickup.");
            }
        }
    }

    private function logHistory(AlterationOrder $order, ?AlterationGarment $garment, ?AlterationTask $task, ?string $from, string $to, ?int $userId, ?string $notes): void
    {
        AlterationStatusHistory::create([
            'alteration_order_id'   => $order->id,
            'alteration_garment_id' => $garment?->id,
            'alteration_task_id'    => $task?->id,
            'from_status'           => $from,
            'to_status'             => $to,
            'changed_by'            => $userId,
            'notes'                 => $notes,
        ]);
    }

    private function notifyCustomer(AlterationOrder $order, string $headline, string $body): void
    {
        try {
            $order->loadMissing('customer');
            $order->customer?->notify(new AlterationOrderNotification($order, $headline, $body));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
