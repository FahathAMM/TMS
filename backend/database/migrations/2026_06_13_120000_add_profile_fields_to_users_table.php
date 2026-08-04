<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('phone');
            $table->string('employee_id', 50)->nullable()->unique()->after('avatar');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('employee_id');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->date('joining_date')->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('joining_date');
            $table->string('emergency_contact', 100)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'employee_id', 'gender', 'date_of_birth', 'joining_date', 'address', 'emergency_contact']);
        });
    }
};
