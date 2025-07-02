<?php

namespace App\Http\Controllers;

use App\Models\DaftarBarang;
use App\Models\DaftarSupplier;
use App\Models\PemasukanBarang;
use App\Models\PengeluaranBahan;
use App\Models\StokBarang;


use Illuminate\Http\Request;

class HeavyObjectController extends Controller
{
   

    // Metode untuk menampilkan halaman Pemasukan Barang
    public function pemasukanBarang()
    {
        $pemasukan_barang = PemasukanBarang::all();

        // Tambahkan baris ini untuk debugging
        // dd($pemasukan_barang);

        return view('panel.heavyobject.pemasukan-barang.index', compact('pemasukan_barang'));
    }

    // Metode untuk menampilkan halaman Pengeluaran Barang
    public function pengeluaranBarang()
    {
        // Logika atau pengambilan data terkait pengeluaran barang
        $pengeluaran_barang = PengeluaranBahan::all();

        // Mengirim data pengeluaran ke view
        return view('panel.heavyobject.pengeluaran-barang.index', compact('pengeluaran_barang'));
    
    }
    // Metode untuk menampilkan halaman Rekap Total
    public function rekapTotal()
    {
        // Logika atau pengambilan data terkait rekap total barang
        return view('panel.heavyobject.rekap-total');
    }
    public function daftarBarang()
    {
        $barang = DaftarBarang::all();
        return view('panel.heavyobject.master-data.daftar-barang.index', compact('barang'));
    }
    public function daftarSupplier()
    {
        $suppliers = DaftarSupplier::all();
        return view('panel.heavyobject.master-data.daftar-suppliers.index', compact('suppliers'));
    }
    public function index()
    {
        $suppliers = DaftarSupplier::all();
        return view('panel.heavyobject.master-data.daftar-suppliers.index', compact('suppliers'));
    }

     // Metode untuk menampilkan halaman Stok Barang
     public function stokBarang()
{
    // Ambil data stok barang dari model StokBarang
    $stok_barang = StokBarang::all();

    // Mengirim data ke view
    return view('panel.heavyobject.stok-barang.index', compact('stok_barang'));
}
}
