<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarBarang extends Model
{
    protected $table = 'daftar_barang';

    protected $fillable = [
        'nama_barang',
        'kode_barang',
        'satuan'
    ];
}
