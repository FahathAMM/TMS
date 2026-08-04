<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete(); // optional — can return without linking a specific PO
            $table->string('reference_number', 50)->unique(); // SRN-20260001
            $table->date('return_date');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'return_date']);
            $table->index('purchase_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_returns');
    }
};
