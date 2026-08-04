<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            // credit = we owe supplier more (purchase received / opening balance)
            // debit  = what we owe decreases (payment made / goods returned to supplier)
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2); // snapshot of supplier.current_balance after this entry
            $table->string('reference_type', 50)->nullable(); // 'purchase','payment','supplier_return','adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('date');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
    }
};
