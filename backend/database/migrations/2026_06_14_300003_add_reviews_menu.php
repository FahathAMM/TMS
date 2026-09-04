<?php

use App\Models\Administration\Menu;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $sSection = Menu::where('slug', 'section_sales')->first();

        $menu = Menu::firstOrNew(['slug' => 'reviews']);
        if (! $menu->exists) {
            $menu->fill([
                'name'       => 'Reviews',
                'route_name' => '/reviews',
                'icon'       => 'Star',
                'sort_order' => 5,
                'parent_id'  => $sSection?->id,
                'is_active'  => true,
            ])->save(); // boot() auto-creates view/create/edit/delete permissions
        }

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo('view reviews');
    }

    public function down(): void
    {
        Menu::where('slug', 'reviews')->first()?->delete();
    }
};
