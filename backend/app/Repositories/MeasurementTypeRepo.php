<?php

namespace App\Repositories;

use App\Models\Tailoring\MeasurementType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class MeasurementTypeRepo extends BaseRepository
{
    protected $model;

    public function __construct(MeasurementType $model)
    {
        $this->model = $model;
    }

    public function getData(Request $request): LengthAwarePaginator|Collection
    {
        $query = $this->model->with(['fields' => fn ($q) => $q->orderBy('sort_order')]);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->boolean('all')) {
            return $query->orderBy('name')->get();
        }

        $sortable = ['name', 'is_active', 'created_at'];
        $sort = in_array($request->sort_field, $sortable) ? $request->sort_field : 'name';
        $dir  = $request->sort_direction === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $dir)->paginate(min($request->perPage ?? 20, 100));
    }
}
