<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query()->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',    'like', "%{$request->search}%")
                  ->orWhere('email',   'like', "%{$request->search}%")
                  ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $messages = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
            'counts' => [
                'all'     => ContactMessage::count(),
                'new'     => ContactMessage::where('status', 'new')->count(),
                'read'    => ContactMessage::where('status', 'read')->count(),
                'replied' => ContactMessage::where('status', 'replied')->count(),
            ],
        ]);
    }

    public function show(ContactMessage $contactMessage): JsonResponse
    {
        if ($contactMessage->status === 'new') {
            $contactMessage->update(['status' => 'read']);
        }

        return response()->json(['data' => $contactMessage]);
    }

    public function updateStatus(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        $request->validate(['status' => 'required|in:new,read,replied']);
        $contactMessage->update(['status' => $request->status]);

        return response()->json(['data' => $contactMessage]);
    }

    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->delete();

        return response()->json(['message' => 'Message deleted']);
    }
}
