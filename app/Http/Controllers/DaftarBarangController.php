<?php

namespace App\Http\Controllers;

use App\Models\DaftarBarang;
use App\Models\LokasiGudang;
use App\Models\KategoriBarang;
use Illuminate\Http\Request;

class DaftarBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(['admin'])->only(['edit', 'update', 'destroy']); 
    }

    public function index()
    {
        $daftarBarang = DaftarBarang::all();
        return view('panel.heavyobject.master-data.daftar-barang.index', compact('daftarBarang'));
    }

    public function create()
    {
        return view('panel.heavyobject.master-data.daftar-barang.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required',
            'kode_barang' => 'required|unique:daftar_barang',
            'satuan' => 'required|in:Pcs,Lembar,Pack,Roll,Unit',
        ]);

        DaftarBarang::create($validated);
        
        return redirect()->route('daftar-barang.index')
            ->with('success', 'Data barang berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $barang = DaftarBarang::findOrFail($id);
        $lokasiGudangs = LokasiGudang::all();
        $kategoriBarangs = KategoriBarang::all();

        return view('panel.heavyobject.master-data.daftar-barang.edit', compact('barang', 'lokasiGudangs', 'kategoriBarangs'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_barang' => 'required',
            'kode_barang' => 'required|unique:daftar_barang,kode_barang,'.$id,
            'satuan' => 'required|in:Pcs,Lembar,Pack,Roll,Unit',
        ]);

        $barang = DaftarBarang::findOrFail($id);
        $barang->update($validated);
        
        return redirect()->route('daftar-barang.index')
            ->with('success', 'Data barang berhasil diupdate!');
    }

    public function destroy($id)
    {
        $barang = DaftarBarang::findOrFail($id);
        $barang->delete();
        
        return redirect()->route('daftar-barang.index')
            ->with('success', 'Barang berhasil dihapus!');
    }
}
