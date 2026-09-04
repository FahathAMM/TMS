<?php

use App\Models\Administration\Menu;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $sSection = Menu::where('slug', 'section_sales')->first();

        $menu = Menu::firstOrNew(['slug' => 'contact_messages']);
        if (! $menu->exists) {
            $menu->fill([
                'name'       => 'Messages',
                'route_name' => '/contact-messages',
                'icon'       => 'MessageSquare',
                'sort_order' => 10,
                'parent_id'  => $sSection?->id,
                'is_active'  => true,
            ])->save(); // boot() auto-creates view/create/edit/delete permissions
        }

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo('view contact_messages');
    }

    public function down(): void
    {
        Menu::where('slug', 'contact_messages')->first()?->delete(); // boot() removes permissions
    }
};
