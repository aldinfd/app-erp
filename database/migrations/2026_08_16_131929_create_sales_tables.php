<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul Sales (database.md v1.2 §5).
     *
     * Tabel: sales_orders, sales_order_items, invoices, payments.
     * Kolom valid `sales_orders.status`: draft, confirmed, paid, cancelled.
     * Kolom valid `payments.method`: midtrans, bank_transfer, cash.
     * Kolom valid `payments.status`: pending, settlement, capture, deny, expire, cancel, refund.
     * Kolom valid `invoices.status`: unpaid, partial, paid, void.
     */
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('order_date');
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('shipping', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'order_date']);
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('sales_order_id')->unique()->constrained('sales_orders')->restrictOnDelete();
            $table->date('issued_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('status', 20)->default('unpaid');
            $table->timestamps();
            $table->index('status');
            $table->index('issued_date');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20);
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_ref', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->datetime('paid_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
