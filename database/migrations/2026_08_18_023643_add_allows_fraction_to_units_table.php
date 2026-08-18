<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tandai satuan yang boleh pecahan (mis. kg) — qty stok & reorder point
     * produk dengan satuan lain wajib bilangan bulat (divalidasi aplikasi).
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('allows_fraction')->default(false)->after('abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('allows_fraction');
        });
    }
};
