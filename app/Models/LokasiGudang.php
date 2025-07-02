<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LokasiGudang extends Model
{
    protected $fillable = ['nama_lokasi', 'keterangan'];

    public function barang()
    {
        return $this->hasMany(DaftarBarang::class, 'lokasi_penyimpanan', 'nama_lokasi');
    }
}
