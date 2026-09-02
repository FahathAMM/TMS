<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Accounting\Expense;
use App\Services\AuthUser;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenseService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Expense::with('createdBy')->latest('expense_date');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($from = $request->get('from')) {
            $query->where('expense_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->where('expense_date', '<=', $to);
        }

        $expenses = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ExpenseResource::collection($expenses->items()),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page'    => $expenses->lastPage(),
                'per_page'     => $expenses->perPage(),
                'total'        => $expenses->total(),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->record($request->validated(), AuthUser::id());

        return response()->json([
            'message' => 'Expense recorded successfully',
            'data'    => new ExpenseResource($expense),
        ], 201);
    }
}
