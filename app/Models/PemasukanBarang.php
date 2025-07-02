<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PemasukanBarang extends Model
{
    use HasFactory;

    protected $table = 'pemasukan_barang';

    protected $fillable = [
        'tanggal_penerimaan',
        'nama_supplier',
        'nomor_po',
        'nama_barang',
        'kode_barang',
        'kategori_barang',
        'jumlah_diterima',
        'satuan',
        'kondisi_barang',
        'lokasi_penyimpanan',
        'nama_petugas',
        'note'
    ];

    protected $dates = [
        'tanggal_penerimaan',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'tanggal_penerimaan' => 'datetime'
    ];

    public function getTanggalPenerimaanFormattedAttribute()
    {
        return Carbon::parse($this->tanggal_penerimaan)->format('d/m/Y');
    }

    public function pengeluaran()
    {
        return $this->hasMany(PengeluaranBahan::class, 'pemasukan_id');
    }

    public function getStokTersediaAttribute()
    {
        return $this->jumlah_diterima - $this->pengeluaran()->sum('jumlah_dikeluarkan');
    }
}
