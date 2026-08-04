<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\JournalEntryResource;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function accounts(): JsonResponse
    {
        return response()->json(['data' => AccountResource::collection(Account::all())]);
    }

    public function journalEntries(Request $request): JsonResponse
    {
        $query = JournalEntry::with('lines.account')->latest('entry_date')->latest('id');

        if ($from = $request->get('from')) {
            $query->where('entry_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->where('entry_date', '<=', $to);
        }

        $entries = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => JournalEntryResource::collection($entries->items()),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
                'per_page'     => $entries->perPage(),
                'total'        => $entries->total(),
            ],
        ]);
    }

    public function trialBalance(): JsonResponse
    {
        return response()->json(['data' => $this->accountingService->trialBalance()]);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->accountingService->profitLoss($request->get('from'), $request->get('to')),
        ]);
    }
}
