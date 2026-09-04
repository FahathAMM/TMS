<?php

use App\Models\Administration\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Deleting the menu row also removes its auto-generated
        // view/create/edit/delete permissions (see Menu::booted 'deleting').
        Menu::where('slug', 'fitting_sessions')->first()?->delete();

        Schema::dropIfExists('fitting_sessions');
    }

    public function down(): void
    {
        Schema::create('fitting_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_date');
            $table->string('status')->default('scheduled');
            $table->text('alteration_notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        $sTailoring = Menu::where('slug', 'section_tailoring')->first();

        $menu = Menu::firstOrNew(['slug' => 'fitting_sessions']);
        if (! $menu->exists) {
            $menu->fill([
                'name'       => 'Fittings',
                'route_name' => '/fittings',
                'icon'       => 'Activity',
                'sort_order' => 3,
                'parent_id'  => $sTailoring?->id,
                'is_active'  => true,
            ])->save();
        }

        foreach (['admin', 'staff'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo('view fitting_sessions');
        }
    }
};
