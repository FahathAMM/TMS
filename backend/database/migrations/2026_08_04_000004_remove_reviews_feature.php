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
        Menu::where('slug', 'reviews')->first()?->delete();

        Schema::dropIfExists('reviews');
    }

    public function down(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->nullable();
            $table->tinyInteger('rating')->unsigned();
            $table->string('title', 200)->nullable();
            $table->text('message');
            $table->string('product_name', 200)->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

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
            ])->save();
        }

        Role::where('name', 'admin')->where('guard_name', 'web')->first()?->givePermissionTo('view reviews');
    }
};
