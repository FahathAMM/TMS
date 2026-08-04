<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('password');
            }
            if (!Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken();
            }
            // Make email unique for auth (only for new rows — existing may have dupes)
            if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email')->nullable();
            }
        });

        // Create customer password resets table
        if (!Schema::hasTable('customer_password_resets')) {
            Schema::create('customer_password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(array_filter(['password', 'email_verified_at', 'remember_token'], function ($col) {
                return Schema::hasColumn('customers', $col);
            }));
        });

        Schema::dropIfExists('customer_password_resets');
    }
};
