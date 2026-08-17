<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul Purchase (database.md v1.2 §6).
     *
     * Tabel: purchase_orders, purchase_order_items, vendor_invoices, vendor_payments.
     * Kolom valid `purchase_orders.status`: draft, ordered, received, paid, cancelled (keputusan #5).
     * Kolom valid `vendor_invoices.status`: unpaid, partial, paid, void.
     * Kolom valid `vendor_payments.method`: bank_transfer, cash.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 30)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'order_date']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 12, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_invoice_number', 50)->unique();
            $table->foreignId('purchase_order_id')->unique()->constrained('purchase_orders')->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('status', 20)->default('unpaid');
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_invoice_id')->constrained('vendor_invoices')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20);
            $table->string('reference_no', 100)->nullable();
            $table->datetime('paid_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('vendor_invoices');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
