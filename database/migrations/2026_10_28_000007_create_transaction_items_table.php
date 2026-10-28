<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // Always positive. Direction comes from the transaction type.
            // Adjustments are approved immediately: StockService stores the absolute
            // difference between counted and current stock, and the direction is
            // stock_after - stock_before.
            $table->unsignedInteger('quantity');
            // Snapshot of the product stock when the transaction was approved.
            // Feeds the stock card (kartu stok) running balance.
            $table->unsignedInteger('stock_before')->nullable();
            $table->unsignedInteger('stock_after')->nullable();
            $table->timestamps();

            $table->unique(['transaction_id', 'product_id']);
        });

        DB::statement('ALTER TABLE transaction_items ADD CONSTRAINT chk_transaction_items_quantity CHECK (quantity > 0)');
        DB::statement('ALTER TABLE transaction_items ADD CONSTRAINT chk_transaction_items_snapshot CHECK ((stock_before IS NULL) = (stock_after IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
