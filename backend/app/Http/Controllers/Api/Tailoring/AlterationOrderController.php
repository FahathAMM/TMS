<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlterationOrder\AdvanceTaskStatusRequest;
use App\Http\Requests\AlterationOrder\AssignTailorRequest;
use App\Http\Requests\AlterationOrder\CancelOrderRequest;
use App\Http\Requests\AlterationOrder\NotifyOrderRequest;
use App\Http\Requests\AlterationOrder\RecordPaymentRequest;
use App\Http\Requests\AlterationOrder\StoreAlterationOrderRequest;
use App\Http\Requests\AlterationOrder\StoreGarmentRequest;
use App\Http\Requests\AlterationOrder\StoreTaskRequest;
use App\Http\Requests\AlterationOrder\UploadPhotoRequest;
use App\Http\Resources\AlterationGarmentPhotoResource;
use App\Http\Resources\AlterationGarmentResource;
use App\Http\Resources\AlterationOrderPaymentResource;
use App\Http\Resources\AlterationOrderResource;
use App\Http\Resources\AlterationTaskAssignmentResource;
use App\Http\Resources\AlterationTaskResource;
use App\Models\AlterationGarment;
use App\Models\AlterationOrder;
use App\Models\AlterationTask;
use App\Services\AlterationOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlterationOrderController extends Controller
{
    public function __construct(private readonly AlterationOrderService $service) {}

    public function index(Request $request): JsonResponse
    {
        $query = AlterationOrder::with(['customer', 'garments.tasks.assignments.tailor'])
            ->withCount('garments')
            ->orderByDesc('created_at');

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

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        $orders = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => AlterationOrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function store(StoreAlterationOrderRequest $request): JsonResponse
    {
        $order = $this->service->createOrder($request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Alteration order received successfully',
            'data'    => new AlterationOrderResource($order),
        ], 201);
    }

    public function show(AlterationOrder $alterationOrder): JsonResponse
    {
        $alterationOrder->load([
            'customer',
            'garments.tasks.alterationType',
            'garments.tasks.assignments.tailor',
            'garments.photos',
            'garments.measurements.measurementField',
            'payments',
            'statusHistory.changedBy',
        ]);

        return response()->json(['data' => new AlterationOrderResource($alterationOrder)]);
    }

    // ─── Garments ──────────────────────────────────────────────────────────────

    public function storeGarment(StoreGarmentRequest $request, AlterationOrder $alterationOrder): JsonResponse
    {
        $garment = $this->service->addGarment($alterationOrder, $request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Garment received successfully',
            'data'    => new AlterationGarmentResource($garment),
        ], 201);
    }

    public function markGarmentDelivered(AlterationOrder $alterationOrder, AlterationGarment $garment): JsonResponse
    {
        $this->authorizeGarment($alterationOrder, $garment);

        $garment = $this->service->markGarmentDelivered($garment, request()->user()?->id);

        return response()->json([
            'message' => 'Garment marked as delivered',
            'data'    => new AlterationGarmentResource($garment),
        ]);
    }

    // ─── Tasks ─────────────────────────────────────────────────────────────────

    public function storeTask(StoreTaskRequest $request, AlterationOrder $alterationOrder, AlterationGarment $garment): JsonResponse
    {
        $this->authorizeGarment($alterationOrder, $garment);

        $task = $this->service->addTask($garment, $request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Task added successfully',
            'data'    => new AlterationTaskResource($task),
        ], 201);
    }

    public function advanceTaskStatus(AdvanceTaskStatusRequest $request, AlterationOrder $alterationOrder, AlterationGarment $garment, AlterationTask $task): JsonResponse
    {
        $this->authorizeTask($alterationOrder, $garment, $task);

        $task = $this->service->advanceTaskStatus($task, $request->validated('status'), $request->user()?->id, $request->get('notes'));

        return response()->json([
            'message' => 'Task status updated',
            'data'    => new AlterationTaskResource($task),
        ]);
    }

    public function assignTailor(AssignTailorRequest $request, AlterationOrder $alterationOrder, AlterationGarment $garment, AlterationTask $task): JsonResponse
    {
        $this->authorizeTask($alterationOrder, $garment, $task);

        $assignment = $this->service->assignTailor($task, $request->validated('tailor_id'));

        return response()->json([
            'message' => 'Tailor assigned successfully',
            'data'    => new AlterationTaskAssignmentResource($assignment->load('tailor')),
        ], 201);
    }

    // ─── Photos ────────────────────────────────────────────────────────────────

    public function uploadPhoto(UploadPhotoRequest $request, AlterationOrder $alterationOrder, AlterationGarment $garment): JsonResponse
    {
        $this->authorizeGarment($alterationOrder, $garment);

        $photo = $this->service->uploadGarmentPhoto($garment, $request->file('file'), $request->validated('type'), $request->user()?->id);

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'data'    => new AlterationGarmentPhotoResource($photo),
        ], 201);
    }

    // ─── Payments ──────────────────────────────────────────────────────────────

    public function payments(AlterationOrder $alterationOrder): JsonResponse
    {
        return response()->json(['data' => AlterationOrderPaymentResource::collection($alterationOrder->payments)]);
    }

    public function storePayment(RecordPaymentRequest $request, AlterationOrder $alterationOrder): JsonResponse
    {
        $payment = $this->service->recordPayment($alterationOrder, $request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'data'    => new AlterationOrderPaymentResource($payment),
        ], 201);
    }

    // ─── Order lifecycle ───────────────────────────────────────────────────────

    public function complete(AlterationOrder $alterationOrder): JsonResponse
    {
        $order = $this->service->completeOrder($alterationOrder, request()->user()?->id);

        return response()->json([
            'message' => 'Alteration order completed successfully',
            'data'    => new AlterationOrderResource($order),
        ]);
    }

    public function cancel(CancelOrderRequest $request, AlterationOrder $alterationOrder): JsonResponse
    {
        $order = $this->service->cancelOrder($alterationOrder, $request->get('reason'), $request->user()?->id);

        return response()->json([
            'message' => 'Alteration order cancelled',
            'data'    => new AlterationOrderResource($order),
        ]);
    }

    public function notify(NotifyOrderRequest $request, AlterationOrder $alterationOrder): JsonResponse
    {
        $this->service->sendManualNotification($alterationOrder, $request->get('message'));

        return response()->json(['message' => 'Notification sent']);
    }

    public function notifications(AlterationOrder $alterationOrder): JsonResponse
    {
        $alterationOrder->loadMissing('customer');

        $notifications = $alterationOrder->customer
            ? $alterationOrder->customer->notifications
                ->filter(fn ($n) => ($n->data['alteration_order_id'] ?? null) === $alterationOrder->id)
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

    // ─── Guards ────────────────────────────────────────────────────────────────

    private function authorizeGarment(AlterationOrder $order, AlterationGarment $garment): void
    {
        abort_if($garment->alteration_order_id !== $order->id, 404);
    }

    private function authorizeTask(AlterationOrder $order, AlterationGarment $garment, AlterationTask $task): void
    {
        $this->authorizeGarment($order, $garment);
        abort_if($task->alteration_garment_id !== $garment->id, 404);
    }
}
