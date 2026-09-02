<?php

namespace App\Http\Controllers\Api\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\Menu;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $menus = Menu::orderBy('sort_order')->get();

        if ($menus->isEmpty()) {
            // No menus seeded yet — fall back to grouping by last word of permission name
            $grouped = Permission::orderBy('name')->get()
                ->groupBy(fn ($p) => collect(explode(' ', $p->name))->last())
                ->map(fn ($group, $slug) => [
                    'menu_id'     => null,
                    'name'        => ucfirst(str_replace('_', ' ', $slug)),
                    'slug'        => $slug,
                    'icon'        => null,
                    'permissions' => $group->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                ])
                ->sortKeys()
                ->values();

            return response()->json(['data' => $grouped]);
        }

        // ── Menu-grouped permissions ───────────────────────────────────────────

        $grouped = $menus->map(function (Menu $menu) {
            $perms = Permission::where('name', 'like', "% {$menu->slug}")
                ->orderByRaw("FIELD(SUBSTRING_INDEX(name, ' ', 1), 'view', 'create', 'edit', 'delete')")
                ->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
                ->values();

            return [
                'menu_id'     => $menu->id,
                'name'        => $menu->name,
                'slug'        => $menu->slug,
                'icon'        => $menu->icon,
                'permissions' => $perms,
            ];
        })->filter(fn ($g) => count($g['permissions']) > 0)->values();

        // ── Orphaned permissions (not matching any menu slug) ─────────────────

        $menuSlugs = $menus->pluck('slug')->toArray();
        $orphaned = Permission::orderBy('name')->get()
            ->filter(function ($p) use ($menuSlugs) {
                $lastWord = collect(explode(' ', $p->name))->last();
                return ! in_array($lastWord, $menuSlugs);
            })
            ->groupBy(fn ($p) => collect(explode(' ', $p->name))->last())
            ->map(fn ($group, $slug) => [
                'menu_id'     => null,
                'name'        => ucfirst(str_replace('_', ' ', $slug)),
                'slug'        => $slug,
                'icon'        => null,
                'permissions' => $group->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            ])
            ->values();

        return response()->json(['data' => [...$grouped, ...$orphaned]]);
    }
}
