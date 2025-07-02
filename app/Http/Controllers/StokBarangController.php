<?php
namespace App\Http\Controllers;

use App\Models\StokBarang;
use App\Models\DaftarBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(['admin'])->only(['edit', 'update', 'destroy']); 
    }

    public function index()
    {
        $stokBarang = DB::table('pemasukan_barang')
            ->select(
                'nama_barang',
                'kode_barang',
                'lokasi_penyimpanan',
                DB::raw('SUM(jumlah_diterima) as total_masuk'),
                DB::raw('(SELECT COALESCE(SUM(jumlah_dikeluarkan), 0) FROM pengeluaran_bahan WHERE kode_barang = pemasukan_barang.kode_barang) as total_keluar')
            )
            ->groupBy('nama_barang', 'kode_barang', 'lokasi_penyimpanan')
            ->get()
            ->map(function($item) {
                $item->stok_tersedia = $item->total_masuk - $item->total_keluar;
                return $item;
            });

        return view('panel.heavyobject.stok-barang.index', compact('stokBarang'));
    }

    public function create()
    {
        $daftarBarang = DaftarBarang::all();
        return view('panel.heavyobject.stok-barang.create', compact('daftarBarang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'barang_id' => 'required|exists:daftar_barang,id',
            'jumlah_stok' => 'required|numeric|min:0',
            'stok_minimum' => 'required|numeric|min:0',
            'lokasi_penyimpanan' => 'required',
            'keterangan' => 'nullable'
        ]);

        StokBarang::create($request->all());
        return redirect()->route('stok-barang.index')->with('success', 'Stok barang berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $stokBarang = StokBarang::findOrFail($id);
        $daftarBarang = DaftarBarang::all();
        return view('panel.heavyobject.stok-barang.edit', compact('stokBarang', 'daftarBarang'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'barang_id' => 'required|exists:daftar_barang,id',
            'jumlah_stok' => 'required|numeric|min:0',
            'stok_minimum' => 'required|numeric|min:0',
            'lokasi_penyimpanan' => 'required',
            'keterangan' => 'nullable'
        ]);

        $stokBarang = StokBarang::findOrFail($id);
        $stokBarang->update($request->all());
        return redirect()->route('stok-barang.index')->with('success', 'Stok barang berhasil diupdate!');
    }

    public function destroy($id)
    {
        $stokBarang = StokBarang::findOrFail($id);
        $stokBarang->delete();
        return redirect()->route('stok-barang.index')->with('success', 'Stok barang berhasil dihapus!');
    }
}
