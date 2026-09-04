<?php

namespace App\Repositories;

use App\Models\Inventory\Supplier;
use App\Models\Inventory\SupplierContact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierRepo
{
    // ─── Listing ──────────────────────────────────────────────────────────────

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = Supplier::withCount('purchases');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('company', 'LIKE', "%{$term}%")
                  ->orWhere('phone', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        match ($request->input('sort', 'newest')) {
            'name_asc'  => $query->orderBy('name'),
            'balance'   => $query->orderBy('current_balance', 'desc'),
            default     => $query->latest(),
        };

        return $query->paginate($request->input('per_page', 15));
    }

    public function findWithDetails(int $id): Supplier
    {
        return Supplier::with(['contacts', 'primaryContact'])->findOrFail($id);
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function store(array $data): Supplier
    {
        return DB::transaction(function () use ($data): Supplier {
            $contacts = $data['contacts'] ?? [];
            unset($data['contacts']);

            $data['current_balance'] = $data['opening_balance'] ?? 0;
            $supplier = Supplier::create($data);

            $this->syncContacts($supplier, $contacts);

            return $this->findWithDetails($supplier->id);
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $this->findWithDetails($supplier->id);
    }

    public function destroy(Supplier $supplier): void
    {
        if ($supplier->purchases()->exists()) {
            abort(422, 'Cannot delete a supplier that has purchases.');
        }

        DB::transaction(function () use ($supplier): void {
            $supplier->contacts()->delete();
            $supplier->ledger()->delete();
            $supplier->delete();
        });
    }

    // ─── Contacts ─────────────────────────────────────────────────────────────

    public function storeContact(Supplier $supplier, array $data): SupplierContact
    {
        if (!empty($data['is_primary'])) {
            $supplier->contacts()->update(['is_primary' => false]);
        }

        return $supplier->contacts()->create($data);
    }

    public function updateContact(SupplierContact $contact, array $data): SupplierContact
    {
        if (!empty($data['is_primary'])) {
            SupplierContact::where('supplier_id', $contact->supplier_id)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $contact->update($data);

        return $contact->fresh();
    }

    public function destroyContact(SupplierContact $contact): void
    {
        $contact->delete();
    }

    // ─── Ledger ───────────────────────────────────────────────────────────────

    public function getLedger(Supplier $supplier, Request $request): LengthAwarePaginator
    {
        $query = $supplier->ledger()->with('createdBy');

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderBy('date', 'desc')->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function syncContacts(Supplier $supplier, array $contacts): void
    {
        $hasPrimary = collect($contacts)->contains('is_primary', true);

        foreach ($contacts as $index => $contact) {
            if (!$hasPrimary && $index === 0) {
                $contact['is_primary'] = true;
            }
            $supplier->contacts()->create($contact);
        }
    }
}
