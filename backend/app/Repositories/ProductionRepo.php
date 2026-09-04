<?php

namespace App\Repositories;

use App\Models\Tailoring\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ProductionRepo extends BaseRepository
{
    protected $model;

    public function __construct(OrderItem $model)
    {
        $this->model = $model;
    }

    public function getData(Request $request): LengthAwarePaginator
    {
        $query = $this->model->with(['order.customer']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('job_card_number', 'like', "%{$search}%")
                  ->orWhere('garment_type', 'like', "%{$search}%")
                  ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")));
            });
        }

        if ($stage = $request->get('stage')) {
            $query->where('production_status', $stage);
        }

        $sortable = ['job_card_number', 'garment_type', 'production_status', 'created_at'];
        $sort = in_array($request->sort_field, $sortable) ? $request->sort_field : 'created_at';
        $dir  = $request->sort_direction === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        return $query->paginate($request->get('perPage', 20));
    }
}
