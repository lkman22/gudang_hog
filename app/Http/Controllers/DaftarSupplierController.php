<?php

namespace App\Http\Controllers;

use App\Models\DaftarSupplier;
use Illuminate\Http\Request;

class DaftarSupplierController extends Controller
{
    public function index()
    {
        $suppliers = DaftarSupplier::all();
        return view('panel.heavyobject.master-data.daftar-supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('panel.heavyobject.master-data.daftar-supplier.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required',
            'no_telp' => 'required',
            'alamat' => 'required',
            'catatan' => 'nullable'
        ]);

        DaftarSupplier::create($validated);
        
        return redirect()->route('daftar-supplier.index')
            ->with('success', 'Data supplier berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $supplier = DaftarSupplier::findOrFail($id);
        $uniqueSuppliers = DaftarSupplier::pluck('nama_supplier')->unique();

        return view('panel.heavyobject.master-data.daftar-supplier.edit', compact('supplier', 'uniqueSuppliers'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required',
            'no_telp' => 'required',
            'alamat' => 'required',
            'catatan' => 'nullable'
        ]);

        $supplier = DaftarSupplier::findOrFail($id);
        $supplier->update($validated);
        
        return redirect()->route('daftar-supplier.index')
            ->with('success', 'Data supplier berhasil diupdate!');
    }

    public function destroy($id)
    {
        $supplier = DaftarSupplier::findOrFail($id);
        $supplier->delete();
        
        return redirect()->route('daftar-supplier.index')
            ->with('success', 'Supplier berhasil dihapus!');
    }
}
