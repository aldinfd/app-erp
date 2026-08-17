<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data master ERP (database.md v1.2 §4).
     *
     * Tabel: categories, units, products, customers, vendors, chart_of_accounts.
     * Kolom valid `chart_of_accounts.type`: asset, liability, equity, revenue, expense.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('abbreviation', 10)->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('stock_qty', 12, 2)->default(0);
            $table->decimal('reorder_point', 12, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('email');
            $table->index('phone');
            $table->index('name');
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->restrictOnDelete();
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');
    }
};
