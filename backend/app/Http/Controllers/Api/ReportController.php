<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function orders(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->ordersSummary($request->get('from'), $request->get('to'))]);
    }

    public function payments(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->paymentsCollected($request->get('from'), $request->get('to'))]);
    }

    public function outstandingBalances(): JsonResponse
    {
        return response()->json(['data' => $this->reportService->outstandingBalances()]);
    }

    public function stock(): JsonResponse
    {
        return response()->json(['data' => $this->reportService->stockSummary()]);
    }

    public function purchases(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->purchasesSummary($request->get('from'), $request->get('to'))]);
    }

    public function expenses(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->expensesSummary($request->get('from'), $request->get('to'))]);
    }

    public function tailorProductivity(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->tailorProductivity($request->get('from'), $request->get('to'))]);
    }

    public function alterationOrders(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->alterationOrdersSummary($request->get('from'), $request->get('to'))]);
    }

    public function alterationRevenue(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reportService->alterationRevenue($request->get('from'), $request->get('to'))]);
    }
}
