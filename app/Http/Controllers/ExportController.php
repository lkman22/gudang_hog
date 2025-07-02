<?php

namespace App\Http\Controllers;

use App\Models\PemasukanBarang;
use Illuminate\Http\Request;
use PDF;
use Excel;
use App\Exports\PemasukanBarangExport;

class ExportController extends Controller
{
    public function export($type)
    {
        $data = PemasukanBarang::with('pengeluaran')->get();
        
        // Tambahkan sisa stok ke setiap record
        foreach($data as $pemasukan) {
            $totalKeluar = $pemasukan->pengeluaran()->sum('jumlah_dikeluarkan');
            $pemasukan->sisa_stok = $pemasukan->jumlah_diterima - $totalKeluar;
        }

        if ($type === 'pdf') {
            $pdf = PDF::loadView('exports.pemasukan-barang-pdf', compact('data'));
            return $pdf->download('pemasukan-barang.pdf');
        }

        if ($type === 'excel') {
            return Excel::download(new PemasukanBarangExport($data), 'pemasukan-barang.xlsx');
        }

        return back()->with('error', 'Format tidak didukung');
    }
}