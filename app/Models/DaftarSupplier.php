<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarSupplier extends Model
{
    protected $table = 'daftar_suppliers';

    protected $fillable = [
        'nama_supplier',
        'no_telp',
        'alamat',
        'catatan'
    ];
}
