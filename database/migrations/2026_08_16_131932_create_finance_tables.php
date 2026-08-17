<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul Finance (database.md v1.2 §8).
     *
     * Tabel: journal_entries, journal_lines, journal_mappings.
     * Kolom valid `journal_entries.source`: sales_payment, purchase_received, purchase_payment, manual.
     * Kolom valid `journal_mappings.transaction_type`: sales_payment, purchase_received, purchase_payment.
     * Kolom valid `journal_mappings.account_key`: kas_bank, pendapatan_penjualan, utang_ppn,
     * persediaan, hpp, hutang_vendor.
     *
     * Jurnal immutable: koreksi lewat jurnal reversal (JournalService).
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 30)->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('source', 30);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('entry_date');
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
        });

        Schema::create('journal_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type', 30);
            $table->string('account_key', 30);
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['transaction_type', 'account_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_mappings');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
    }
};
