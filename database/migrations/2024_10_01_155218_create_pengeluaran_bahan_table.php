<?php

// database/migrations/xxxx_xx_xx_create_pengeluaran_bahan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePengeluaranBahanTable extends Migration
{
    public function up()
    {
        Schema::create('pengeluaran_bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemasukan_id')->constrained('pemasukan_barang')->onDelete('cascade');
            $table->dateTime('tanggal_pengeluaran');
            $table->string('nama_barang');
            $table->string('kode_barang');
            $table->integer('jumlah_dikeluarkan');
            $table->string('satuan');
            $table->string('lokasi_tujuan');
            $table->string('nama_penerima');
            $table->string('nama_petugas');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pengeluaran_bahan');
    }
}
