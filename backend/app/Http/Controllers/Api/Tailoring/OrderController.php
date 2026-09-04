<?php

namespace App\Http\Controllers\Api\Tailoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AdvanceStatusRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderPaymentResource;
use App\Http\Resources\OrderResource;
use App\Http\Requests\Order\AssignTailorRequest;
use App\Http\Requests\Order\NotifyOrderRequest;
use App\Http\Requests\Order\QcRequest;
use App\Http\Requests\Order\RecordPaymentRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Tailoring\Order;
use App\Models\Tailoring\OrderItem;
use App\Repositories\OrderRepo;
use App\Services\AuditService;
use App\Services\AuthUser;
use App\Traits\JsonResponse as JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use JsonResponseTrait;

    protected string $modelName = 'Order';
    protected string $routeName = 'orders';
    protected bool $isDestroyingAllowed;
    protected Order $model;
    protected OrderRepo $repo;

    public function __construct(Order $model, OrderRepo $repo)
    {
        $this->model                = $model;
        $this->repo                 = $repo;
        $this->isDestroyingAllowed = false;
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->repo->getData($request);

        AuditService::view('Order', null, '');

        return response()->json([
            'record'  => $orders,
            'message' => "{$this->modelName}s retrieved successfully",
            'status'  => true,
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->repo->createOrder($request->validated(), AuthUser::id());

            AuditService::create('Order', $order->id, $order->order_number);

            Log::info('Order Create', ['order_id' => $order->id, 'created_by' => AuthUser::id()]);

            return $this->response("{$this->modelName} created successfully", new OrderResource($order), 201);
        } catch (\Throwable $th) {
            Log::error('OrderController@store', ['message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'items.materials.product', 'items.assignments.tailor', 'items.measurementType', 'items.measurements.measurementField', 'payments']);

        AuditService::view('Order', $order->id, $order->order_number);

        return $this->response("{$this->modelName} retrieved successfully", new OrderResource($order));
    }

    public function complete(Order $order): JsonResponse
    {
        try {
            $order = $this->repo->completeOrder($order, AuthUser::id());

            AuditService::edit('Order', $order->id, $order->order_number);

            Log::info('Order Complete', ['order_id' => $order->id, 'completed_by' => AuthUser::id()]);

            return $this->response("{$this->modelName} completed successfully", new OrderResource($order->load(['customer', 'items.materials.product', 'items.assignments.tailor', 'items.measurementType', 'items.measurements.measurementField', 'payments'])));
        } catch (\Throwable $th) {
            Log::error('OrderController@complete', ['order_id' => $order->id, 'message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    // ─── Payments ──────────────────────────────────────────────────────────────

    public function payments(Order $order): JsonResponse
    {
        return $this->response('Payments retrieved successfully', OrderPaymentResource::collection($order->payments));
    }

    public function storePayment(RecordPaymentRequest $request, Order $order): JsonResponse
    {
        try {
            $payment = $this->repo->recordPayment($order, $request->validated(), AuthUser::id());

            AuditService::edit('Order', $order->id, $order->order_number);

            Log::info(
                'Order Payment',
                ['order_id' => $order->id, 'payment_id' => $payment->id, 'recorded_by' => AuthUser::id()]
            );

            return $this->response('Payment recorded successfully', new OrderPaymentResource($payment), 201);
        } catch (\Throwable $th) {
            Log::error('OrderController@storePayment', ['order_id' => $order->id, 'message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    // ─── Item production actions ──────────────────────────────────────────────

    public function advanceItemStatus(AdvanceStatusRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        try {
            $item = $this->repo->advanceProductionStatus($item, $request->validated('production_status'), AuthUser::id());

            AuditService::edit('Order', $order->id, $order->order_number);

            Log::info('Order Item Status Update', ['order_id' => $order->id, 'item_id' => $item->id, 'status' => $item->production_status, 'updated_by' => AuthUser::id()]);

            return $this->response('Production status updated', new OrderItemResource($item->load(['materials.product', 'assignments.tailor', 'measurementType', 'measurements.measurementField'])));
        } catch (\Throwable $th) {
            Log::error('OrderController@advanceItemStatus', ['order_id' => $order->id, 'item_id' => $item->id, 'message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    public function qcItem(QcRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        try {
            $item = $this->repo->recordQc($item, $request->boolean('passed'), $request->get('notes'));

            AuditService::edit('Order', $order->id, $order->order_number);

            Log::info('Order Item QC', ['order_id' => $order->id, 'item_id' => $item->id, 'passed' => $request->boolean('passed'), 'recorded_by' => AuthUser::id()]);

            return $this->response(
                $item->production_status === 'ready' ? 'QC passed' : 'QC failed — sent to rework',
                new OrderItemResource($item->load(['materials.product', 'assignments.tailor', 'measurementType', 'measurements.measurementField'])),
            );
        } catch (\Throwable $th) {
            Log::error('OrderController@qcItem', ['order_id' => $order->id, 'item_id' => $item->id, 'message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    public function assignTailor(AssignTailorRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        try {
            $this->repo->assignTailor($item, $request->validated('tailor_id'), $request->get('assigned_role'));

            AuditService::edit('Order', $order->id, $order->order_number);

            Log::info('Order Item Tailor Assign', ['order_id' => $order->id, 'item_id' => $item->id, 'tailor_id' => $request->validated('tailor_id'), 'assigned_by' => AuthUser::id()]);

            return $this->response('Tailor assigned successfully', new OrderItemResource($item->fresh(['materials.product', 'assignments.tailor', 'measurementType', 'measurements.measurementField'])));
        } catch (\Throwable $th) {
            Log::error('OrderController@assignTailor', ['order_id' => $order->id, 'item_id' => $item->id, 'message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    // ─── Customer notifications ────────────────────────────────────────────────

    public function notify(NotifyOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $this->repo->sendManualNotification($order, $request->get('message'));

            AuditService::edit('Order', $order->id, $order->order_number);

            Log::info('Order Notify', ['order_id' => $order->id, 'sent_by' => AuthUser::id()]);

            return $this->response('Notification sent');
        } catch (\Throwable $th) {
            Log::error('OrderController@notify', ['order_id' => $order->id, 'message' => $th->getMessage(), 'attempted_by' => AuthUser::id()]);

            return $this->response($th->getMessage(), null, 422);
        }
    }

    public function notifications(Order $order): JsonResponse
    {
        $order->loadMissing('customer');

        $notifications = $order->customer
            ? $order->customer->notifications
            ->filter(fn($n) => ($n->data['order_id'] ?? null) === $order->id)
            ->values()
            : collect();

        return $this->response('Notifications retrieved successfully', $notifications->map(fn($n) => [
            'id'         => $n->id,
            'headline'   => $n->data['headline'] ?? null,
            'body'       => $n->data['body'] ?? null,
            'created_at' => $n->created_at?->toISOString(),
        ])->values()->all());
    }
}
