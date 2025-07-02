<?php
// app/Models/PengeluaranBahan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PengeluaranBahan extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran_bahan';
    
    protected $fillable = [
        'tanggal_pengeluaran',
        'nama_barang',
        'kode_barang',
        'jumlah_dikeluarkan',
        'satuan',
        'lokasi_tujuan',
        'nama_penerima',
        'nama_petugas',
        'note',
        'pemasukan_id'
    ];

    protected $dates = [
        'tanggal_pengeluaran',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'tanggal_pengeluaran' => 'datetime'
    ];

    public function getTanggalPengeluaranFormattedAttribute()
    {
        return Carbon::parse($this->tanggal_pengeluaran)->format('d/m/Y');
    }

    public function pemasukan()
    {
        return $this->belongsTo(PemasukanBarang::class, 'pemasukan_id');
    }
}
