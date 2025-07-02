<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriBarang;

class KategoriBarangSeeder extends Seeder
{
    public function run()
    {
        $kategoris = [
            ['nama_kategori' => 'Alat Berat'],
            ['nama_kategori' => 'Alat Ringan'],
            ['nama_kategori' => 'Sparepart'],
            ['nama_kategori' => 'Material'],
            ['nama_kategori' => 'ATK'],
        ];

        foreach ($kategoris as $kategori) {
            KategoriBarang::create($kategori);
        }
    }
} 