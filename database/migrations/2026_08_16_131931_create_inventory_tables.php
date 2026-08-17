<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul Inventory (database.md v1.2 §7).
     *
     * Tabel: stock_movements — append-only (jejak audit stok).
     * Kolom valid `type`: in, out, adjust.
     * `qty` adalah signed delta (keputusan #2): in = positif, out = negatif, adjust = ±.
     * Invariant: after_qty = before_qty + qty (divalidasi StockService).
     * Tanpa `updated_at` karena baris tidak pernah diubah setelah dibuat.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('type', 10);
            $table->decimal('qty', 12, 2);
            $table->decimal('before_qty', 12, 2);
            $table->decimal('after_qty', 12, 2);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
