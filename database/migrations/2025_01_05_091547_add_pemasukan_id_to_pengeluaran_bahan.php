<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengeluaran_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('pengeluaran_bahan', 'pemasukan_id')) {
                $table->foreignId('pemasukan_id')->nullable()->after('note')
                    ->constrained('pemasukan_barang')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluaran_bahan', function (Blueprint $table) {
            $table->dropForeign(['pemasukan_id']);
            $table->dropColumn('pemasukan_id');
        });
    }
};
