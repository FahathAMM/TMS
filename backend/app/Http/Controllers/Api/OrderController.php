<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AdvanceStatusRequest;
use App\Http\Requests\Order\AssignTailorRequest;
use App\Http\Requests\Order\NotifyOrderRequest;
use App\Http\Requests\Order\QcRequest;
use App\Http\Requests\Order\RecordPaymentRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderPaymentResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\TailoringOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly TailoringOrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'items'])->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Order created successfully',
            'data'    => new OrderResource($order),
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'items.materials.product', 'items.assignments.tailor', 'items.measurementType', 'items.measurements.measurementField', 'payments']);

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        $order = $this->orderService->completeOrder($order, $request->user()?->id);

        return response()->json([
            'message' => 'Order completed successfully',
            'data'    => new OrderResource($order),
        ]);
    }

    // ─── Payments ──────────────────────────────────────────────────────────────

    public function payments(Order $order): JsonResponse
    {
        return response()->json(['data' => OrderPaymentResource::collection($order->payments)]);
    }

    public function storePayment(RecordPaymentRequest $request, Order $order): JsonResponse
    {
        $payment = $this->orderService->recordPayment($order, $request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'data'    => new OrderPaymentResource($payment),
        ], 201);
    }

    // ─── Item production actions ──────────────────────────────────────────────

    public function advanceItemStatus(AdvanceStatusRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $item = $this->orderService->advanceProductionStatus($item, $request->validated('production_status'), $request->user()?->id);

        return response()->json([
            'message' => 'Production status updated',
            'data'    => new OrderItemResource($item),
        ]);
    }

    public function qcItem(QcRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $item = $this->orderService->recordQc($item, $request->boolean('passed'), $request->get('notes'));

        return response()->json([
            'message' => $item->production_status === 'ready' ? 'QC passed' : 'QC failed — sent to rework',
            'data'    => new OrderItemResource($item),
        ]);
    }

    public function assignTailor(AssignTailorRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->orderService->assignTailor($item, $request->validated('tailor_id'), $request->get('assigned_role'));

        return response()->json([
            'message' => 'Tailor assigned successfully',
            'data'    => new OrderItemResource($item->fresh(['assignments.tailor'])),
        ]);
    }

    // ─── Customer notifications ────────────────────────────────────────────────

    public function notify(NotifyOrderRequest $request, Order $order): JsonResponse
    {
        $this->orderService->sendManualNotification($order, $request->get('message'));

        return response()->json(['message' => 'Notification sent']);
    }

    public function notifications(Order $order): JsonResponse
    {
        $order->loadMissing('customer');

        $notifications = $order->customer
            ? $order->customer->notifications
                ->filter(fn ($n) => ($n->data['order_id'] ?? null) === $order->id)
                ->values()
            : collect();

        return response()->json([
            'data' => $notifications->map(fn ($n) => [
                'id'         => $n->id,
                'headline'   => $n->data['headline'] ?? null,
                'body'       => $n->data['body'] ?? null,
                'created_at' => $n->created_at?->toISOString(),
            ]),
        ]);
    }
}
