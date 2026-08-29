<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteration_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteration_order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash');
            $table->string('payment_type'); // advance, balance, full
            $table->string('transaction_reference')->nullable();
            $table->timestamp('paid_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteration_order_payments');
    }
};
