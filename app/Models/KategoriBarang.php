<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriBarang extends Model
{
    protected $fillable = ['nama_kategori', 'keterangan'];

    public function barang()
    {
        return $this->hasMany(DaftarBarang::class, 'kategori_barang', 'nama_kategori');
    }
}
