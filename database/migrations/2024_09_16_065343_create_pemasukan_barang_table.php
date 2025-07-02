<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pemasukan_barang', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_penerimaan');
            $table->string('nama_supplier');
            $table->string('nomor_po');
            $table->string('nama_barang');
            $table->string('kode_barang');
            $table->string('kategori_barang')->nullable();
            $table->integer('jumlah_diterima');
            $table->string('satuan');
            $table->string('kondisi_barang');
            $table->string('lokasi_penyimpanan');
            $table->string('nama_petugas');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pemasukan_barang');
    }
};
