<?php

namespace App\Repositories;

use App\Models\Administration\Menu;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuRepo
{
    public function __construct(private Menu $model) {}

    public function getData(Request $request): LengthAwarePaginator
    {
        $sortable = ['name', 'route_name', 'sort_order', 'created_at'];
        $sort = in_array($request->sort_field, $sortable) ? $request->sort_field : 'sort_order';
        $dir  = $request->sort_direction === 'desc' ? 'desc' : 'asc';

        return $this->model
            ->with('parent')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy($sort, $dir)
            ->orderBy('id')
            ->paginate(min($request->perPage ?? 50, 100));
    }

    public function getFlat(): Collection
    {
        return $this->model->active()->orderBy('sort_order')->get();
    }

    public function getTree(): Collection
    {
        return $this->model
            ->with('children.children') // two levels deep
            ->active()
            ->roots()
            ->orderBy('sort_order')
            ->get();
    }

    public function store(array $data): Menu
    {
        return DB::transaction(function () use ($data) {
            $menu = $this->model->create($data);

            // Ensure admin role always has all permissions (including newly created ones)
            $admin = Role::findByName('admin', 'web');
            $admin->syncPermissions(Permission::where('guard_name', 'web')->get());
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $menu;
        });
    }

    public function update(Menu $menu, array $data): Menu
    {
        $menu->update($data);
        return $menu->fresh('parent');
    }

    public function destroy(Menu $menu): void
    {
        DB::transaction(function () use ($menu) {
            // Re-parent direct children to this menu's parent (or to root)
            $this->model->where('parent_id', $menu->id)
                ->update(['parent_id' => $menu->parent_id]);

            $menu->delete(); // triggers static::deleting → removes permissions
        });
    }

    /**
     * Return ordered menu tree filtered to items the given user can view.
     */
    public function getNavigation(User $user): Collection
    {
        // Load all active menus ordered by sort_order
        $all = $this->model
            ->active()
            ->orderBy('sort_order')
            ->get();

        // Section headers (no route_name) have no permissions — always include them.
        // Navigable menus require "view {slug}" permission if that permission exists.
        $accessible = $all->filter(function (Menu $menu) use ($user) {
            if (empty($menu->route_name)) return true; // section header: always keep for now

            $permName = "view {$menu->slug}";
            $exists   = Permission::where('name', $permName)->exists();
            return !$exists || $user->can($permName);
        });

        $tree = $this->buildTree($accessible);

        // Drop section headers whose children are all hidden (empty sections look broken)
        return $tree->filter(function (Menu $menu) {
            return !empty($menu->route_name) || $menu->children->count() > 0;
        })->values();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildTree(Collection $menus, ?int $parentId = null): Collection
    {
        return $menus
            ->filter(fn ($m) => $m->parent_id === $parentId)
            ->map(function (Menu $menu) use ($menus) {
                $children = $this->buildTree($menus, $menu->id);
                $menu->setRelation('children', $children);
                return $menu;
            })
            ->values();
    }
}
