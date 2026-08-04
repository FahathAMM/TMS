<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->enum('type', [
                'purchase_in',          // received from supplier
                'sale_out',             // sold to customer
                'supplier_return_out',  // returned to supplier (stock leaves)
                'customer_return_in',   // customer returned to us (stock comes back)
                'adjustment_in',        // manual positive correction
                'adjustment_out',       // manual negative correction
                'opening_stock',        // initial stock entry
            ]);

            // quantity is always positive; direction is determined by type
            $table->decimal('quantity', 10, 3);
            $table->decimal('quantity_before', 10, 3);   // snapshot before movement
            $table->decimal('quantity_after', 10, 3);    // snapshot after  movement

            $table->decimal('cost_price', 12, 2)->nullable(); // for in-movements (FIFO/AVCO later)

            // Polymorphic reference — stores 'purchase', 'sale', 'supplier_return', 'sale_return', 'adjustment'
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->date('date');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Core query patterns
            $table->index(['product_id', 'date']);           // product history timeline
            $table->index(['variant_id', 'date']);
            $table->index(['reference_type', 'reference_id']); // "all movements for purchase #5"
            $table->index(['type', 'date']);                 // reporting by movement type
            $table->index('date');                           // date-range reports
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
