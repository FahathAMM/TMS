<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                ->nullable()
                ->constrained('sales')
                ->nullOnDelete(); // optional — walk-in returns without original receipt
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('reference_number', 50)->unique(); // CRN-20260001
            $table->date('return_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->enum('refund_type', ['cash', 'wallet', 'store_credit', 'replace', 'none'])->default('cash');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('sale_id');
            $table->index(['customer_id', 'return_date']);
            $table->index('status');
            $table->index('return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
