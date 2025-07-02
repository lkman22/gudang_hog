<?php

namespace App\Http\Controllers;

use App\Models\PemasukanBarang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DaftarBarang;
use Carbon\Carbon;
use App\Models\PengeluaranBahan;
use App\Models\DaftarSupplier;
use App\Models\LokasiGudang;
use App\Models\KategoriBarang;

class PemasukanBarangController extends Controller
{
    public function index(Request $request)
    {
        $query = PemasukanBarang::query();

        // Filter hanya barang yang belum dikeluarkan atau masih memiliki sisa
        $query->whereRaw('jumlah_diterima > (SELECT COALESCE(SUM(jumlah_dikeluarkan), 0) 
            FROM pengeluaran_bahan 
            WHERE pemasukan_id = pemasukan_barang.id)');

        // Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('kategori_barang', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori')) {
            $query->where('kategori_barang', $request->kategori);
        }

        // Filter lokasi gudang
        if ($request->filled('lokasi')) {
            $query->where('lokasi_penyimpanan', $request->lokasi);
        }

        // Filter waktu
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'today':
                    $query->whereDate('tanggal_penerimaan', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('tanggal_penerimaan', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('tanggal_penerimaan', Carbon::now()->month)
                          ->whereYear('tanggal_penerimaan', Carbon::now()->year);
                    break;
                case 'quarter':
                    $query->whereBetween('tanggal_penerimaan', [
                        Carbon::now()->startOfQuarter(),
                        Carbon::now()->endOfQuarter()
                    ]);
                    break;
                case 'half':
                    $query->whereBetween('tanggal_penerimaan', [
                        Carbon::now()->subMonths(6),
                        Carbon::now()
                    ]);
                    break;
                case 'year':
                    $query->whereYear('tanggal_penerimaan', Carbon::now()->year);
                    break;
            }
        }

        $pemasukan_barang = $query->latest('tanggal_penerimaan')->paginate(10);
        
        // Tambahkan informasi sisa stok untuk setiap pemasukan
        foreach($pemasukan_barang as $pemasukan) {
            $totalKeluar = $pemasukan->pengeluaran()->sum('jumlah_dikeluarkan');
            $pemasukan->sisa_stok = $pemasukan->jumlah_diterima - $totalKeluar;
        }

        // Ambil daftar kategori yang sudah ada untuk filter
        $kategoris = PemasukanBarang::select('kategori_barang')
            ->distinct()
            ->whereNotNull('kategori_barang')
            ->pluck('kategori_barang');

        // Ambil daftar lokasi untuk filter
        $lokasis = PemasukanBarang::select('lokasi_penyimpanan')
            ->distinct()
            ->whereNotNull('lokasi_penyimpanan')
            ->pluck('lokasi_penyimpanan');

        return view('panel.heavyobject.pemasukan-barang.index', 
            compact('pemasukan_barang', 'kategoris', 'lokasis'));
    }

    public function create()
    {
        $daftarBarang = DaftarBarang::all();
        $suppliers = DaftarSupplier::all();
        $lokasiGudangs = LokasiGudang::all();
        $kategoriBarangs = KategoriBarang::all();
        
        return view('panel.heavyobject.pemasukan-barang.create', compact(
            'daftarBarang', 
            'suppliers', 
            'lokasiGudangs',
            'kategoriBarangs'
        ));
    }

    // Tambahkan method baru untuk mengambil data barang sebelumnya
    public function getLastBarang(Request $request)
    {
        $kodeBarang = $request->kode_barang;
        
        // Hitung total stok
        $totalMasuk = PemasukanBarang::where('kode_barang', $kodeBarang)
            ->sum('jumlah_diterima');
        
        $totalKeluar = PengeluaranBahan::where('kode_barang', $kodeBarang)
            ->sum('jumlah_dikeluarkan');
        
        $stokTersedia = $totalMasuk - $totalKeluar;
        
        $lastBarang = PemasukanBarang::where('kode_barang', $kodeBarang)
            ->latest('tanggal_penerimaan')
            ->first();
            
        if ($lastBarang) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nama_barang' => $lastBarang->nama_barang,
                    'kategori_barang' => $lastBarang->kategori_barang,
                    'satuan' => $lastBarang->satuan,
                    'nama_supplier' => $lastBarang->nama_supplier,
                    'kondisi_barang' => $lastBarang->kondisi_barang,
                    'lokasi_penyimpanan' => $lastBarang->lokasi_penyimpanan,
                    'stok_tersedia' => $stokTersedia
                ]
            ]);
        }
        
        return response()->json(['success' => false]);
    }

    // simpan menyimpan
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_penerimaan' => 'required|date',
            'nama_supplier' => 'required',
            'nomor_po' => 'required',
            'barang_id' => 'required|exists:daftar_barang,id',
            'kategori_barang' => 'required',
            'jumlah_diterima' => 'required|numeric|min:1',
            'kondisi_barang' => 'required|in:baik,rusak,cacat,segel,fresh,ex tele',
            'lokasi_penyimpanan' => 'required|exists:lokasi_gudangs,nama_lokasi',
            'nama_petugas' => 'required',
            'note' => 'nullable'
        ]);

        // Ambil data barang dari master
        $barang = DaftarBarang::findOrFail($request->barang_id);

        // Buat record pemasukan barang
        PemasukanBarang::create([
            'tanggal_penerimaan' => $request->tanggal_penerimaan,
            'nama_supplier' => $request->nama_supplier,
            'nomor_po' => $request->nomor_po,
            'nama_barang' => $barang->nama_barang,
            'kode_barang' => $barang->kode_barang,
            'kategori_barang' => $request->kategori_barang,
            'jumlah_diterima' => $request->jumlah_diterima,
            'satuan' => $barang->satuan,
            'kondisi_barang' => $request->kondisi_barang,
            'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
            'nama_petugas' => $request->nama_petugas,
            'note' => $request->note
        ]);

        return redirect()->route('pemasukan-barang.index')
            ->with('success', 'Data pemasukan berhasil ditambahkan!');
    }

    public function pemasukanBarang()
    {
        $pemasukan_barang = PemasukanBarang::all();
        return view('panel.heavyobject.pemasukan-barang.index', compact('pemasukan_barang'));
    }

    // Show the form for editing the specified resource
    public function edit($id)
    {
        $pemasukan_barang = PemasukanBarang::findOrFail($id);
        $daftarBarang = DaftarBarang::all();
        $suppliers = DaftarSupplier::all();
        $lokasiGudangs = LokasiGudang::all();
        
        return view('panel.heavyobject.pemasukan-barang.edit', compact(
            'pemasukan_barang',
            'daftarBarang',
            'suppliers',
            'lokasiGudangs'
        ));
    }
    
    // Update the specified resource in storage
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'barang_id' => 'required|exists:daftar_barang,id',
            'tanggal_penerimaan' => 'required|date',
            'nama_supplier' => 'required',
            'nomor_po' => 'required',
            'kategori_barang' => 'required',
            'jumlah_diterima' => 'required|numeric|min:1',
            'kondisi_barang' => 'required|in:baik,rusak,cacat,segel,fresh,ex tele',
            'lokasi_penyimpanan' => 'required|exists:lokasi_gudangs,nama_lokasi',
            'nama_petugas' => 'required',
            'note' => 'nullable'
        ]);

        $barang = DaftarBarang::findOrFail($request->barang_id);
        $pemasukan_barang = PemasukanBarang::findOrFail($id);

        // Hitung selisih jumlah untuk update stok master
        $selisih = $validated['jumlah_diterima'] - $pemasukan_barang->jumlah_diterima;

        // Cek apakah ada pengeluaran yang terkait
        $totalPengeluaran = $pemasukan_barang->pengeluaran()->sum('jumlah_dikeluarkan');
        
        if ($validated['jumlah_diterima'] < $totalPengeluaran) {
            return back()
                ->withInput()
                ->with('error', "Tidak bisa mengubah jumlah diterima menjadi lebih kecil dari total pengeluaran ($totalPengeluaran)");
        }

        // Update pemasukan barang
        $pemasukan_barang->update([
            'tanggal_penerimaan' => $validated['tanggal_penerimaan'],
            'nama_supplier' => $validated['nama_supplier'],
            'nomor_po' => $validated['nomor_po'],
            'nama_barang' => $barang->nama_barang,
            'kode_barang' => $barang->kode_barang,
            'kategori_barang' => $request->kategori_barang,
            'jumlah_diterima' => $validated['jumlah_diterima'],
            'satuan' => $barang->satuan,
            'kondisi_barang' => $validated['kondisi_barang'],
            'lokasi_penyimpanan' => $validated['lokasi_penyimpanan'],
            'nama_petugas' => $validated['nama_petugas'],
            'note' => $request->note
        ]);

        // Update stok di master data barang
        if ($selisih != 0) {
            $barang->increment('jumlah_diterima', $selisih);
        }

        return redirect()->route('pemasukan-barang.index')
            ->with('success', 'Data pemasukan barang berhasil diupdate');
    }

    // Remove the specified resource from storage
    public function destroy($id)
    {
        $pemasukan = PemasukanBarang::findOrFail($id);
        $pemasukan->delete();
        
        return redirect()->route('pemasukan-barang.index')
            ->with('success', 'Data pemasukan berhasil dihapus!');
    }

    public function showUpdateStok($id)
    {
        $pemasukan_barang = PemasukanBarang::findOrFail($id);
        $totalKeluar = $pemasukan_barang->pengeluaran()->sum('jumlah_dikeluarkan');
        $pemasukan_barang->sisa_stok = $pemasukan_barang->jumlah_diterima - $totalKeluar;
        
        return view('panel.heavyobject.pemasukan-barang.update-stok', compact('pemasukan_barang'));
    }

    public function updateStok(Request $request, $id)
    {
        $request->validate([
            'jumlah_tambah' => 'required|numeric|min:1',
            'tanggal_penerimaan' => 'required|date',
            'note' => 'nullable'
        ]);

        $pemasukan_barang = PemasukanBarang::findOrFail($id);

        // Update jumlah pada record pemasukan barang
        $pemasukan_barang->increment('jumlah_diterima', $request->jumlah_tambah);
        
        // Update catatan jika ada
        if ($request->filled('note')) {
            $note = $pemasukan_barang->note ?? '';
            $pemasukan_barang->update([
                'note' => $note . "\n" . $request->note . ' (Update Stok +' . $request->jumlah_tambah . ')'
            ]);
        }

        return redirect()->route('pemasukan-barang.index')
            ->with('success', 'Stok berhasil diupdate');
    }
}
