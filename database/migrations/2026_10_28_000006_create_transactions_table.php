<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header of every stock movement: goods in, goods out (request), or adjustment.
        // Rows are never edited after approval and never deleted.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->date('transaction_date');
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('recipient_name', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('transaction_date');
        });

        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type CHECK (type IN ('in', 'out', 'adjustment'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_status CHECK (status IN ('pending', 'approved', 'rejected'))");
        // in = from a supplier, out = to a department, adjustment = neither.
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_party CHECK (
            (type = 'in' AND supplier_id IS NOT NULL AND department_id IS NULL)
            OR (type = 'out' AND supplier_id IS NULL AND department_id IS NOT NULL)
            OR (type = 'adjustment' AND supplier_id IS NULL AND department_id IS NULL)
        )");
        // A decided transaction must always say who decided and when.
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_decision CHECK (
            (status = 'pending' AND approved_by IS NULL AND approved_at IS NULL)
            OR (status <> 'pending' AND approved_by IS NOT NULL AND approved_at IS NOT NULL)
        )");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_reject_reason CHECK (status <> 'rejected' OR reject_reason IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
