<?php

namespace App\Http\Controllers;

use App\Models\PengeluaranBahan;
use App\Models\PemasukanBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Pengeluaran Bahan naon anjenggg

class PengeluaranBahanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        // Query untuk stok barang yang tersedia
        $pemasukanBarang = PemasukanBarang::select(
            'id',
            'tanggal_penerimaan',
            'nama_supplier',
            'kode_barang',
            'nama_barang',
            'kategori_barang',
            'jumlah_diterima',
            'satuan',
            'kondisi_barang',
            'lokasi_penyimpanan',
            DB::raw('(SELECT COALESCE(SUM(jumlah_dikeluarkan), 0) 
                    FROM pengeluaran_bahan 
                    WHERE pemasukan_barang.id = pengeluaran_bahan.pemasukan_id) as total_keluar')
        )
        ->whereRaw('jumlah_diterima > (
            SELECT COALESCE(SUM(jumlah_dikeluarkan), 0) 
            FROM pengeluaran_bahan 
            WHERE pemasukan_barang.id = pengeluaran_bahan.pemasukan_id
        )');

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $pemasukanBarang->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('kategori_barang', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori')) {
            $pemasukanBarang->where('kategori_barang', $request->kategori);
        }

        $pemasukanBarang = $pemasukanBarang->get()
            ->map(function($item) {
                $item->stok_tersedia = $item->jumlah_diterima - $item->total_keluar;
                return $item;
            });

        // Query untuk riwayat pengeluaran
        $pengeluaranBarang = PengeluaranBahan::latest('tanggal_pengeluaran')->paginate(10);

        // Ambil daftar kategori untuk filter
        $kategoris = PemasukanBarang::select('kategori_barang')
            ->distinct()
            ->whereNotNull('kategori_barang')
            ->pluck('kategori_barang');

        return view('panel.heavyobject.pengeluaran-barang.index', 
            compact('pemasukanBarang', 'pengeluaranBarang', 'kategoris'));
    }

    public function create()
    {
        // Ambil data pemasukan barang yang masih gaduh stok
        $pemasukanBarang = PemasukanBarang::select(
            'id',
            'tanggal_penerimaan',
            'kode_barang',
            'nama_barang',
            'jumlah_diterima',
            'satuan',
            'lokasi_penyimpanan',
            //ngetang
            DB::raw('(SELECT COALESCE(SUM(jumlah_dikeluarkan), 0) FROM pengeluaran_bahan WHERE pemasukan_id = pemasukan_barang.id) as jumlah_keluar')
        )
        ->havingRaw('jumlah_diterima > COALESCE(jumlah_keluar, 0)')
        ->get()
        ->map(function($item) {
            $item->stok_tersedia = $item->jumlah_diterima - $item->jumlah_keluar;
            return $item;
        });

        return view('panel.heavyobject.pengeluaran-barang.create', compact('pemasukanBarang'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pemasukan_id' => 'required|exists:pemasukan_barang,id',
            'jumlah_dikeluarkan' => 'required|numeric|min:1',
            'lokasi_tujuan' => 'required',
            'nama_penerima' => 'required',
            'nama_petugas' => 'required'
        ]);

        try {
            $pemasukan = PemasukanBarang::findOrFail($validated['pemasukan_id']);
            
            // Hitung total stok tersedia untuk kode barang ini
            $totalStok = PemasukanBarang::where('kode_barang', $pemasukan->kode_barang)
                ->sum('jumlah_diterima');
            
            $totalKeluar = PengeluaranBahan::where('kode_barang', $pemasukan->kode_barang)
                ->sum('jumlah_dikeluarkan');
            
            $stokTersedia = $totalStok - $totalKeluar;

            if ($validated['jumlah_dikeluarkan'] > $stokTersedia) {
                return back()->with('error', 
                    "Stok tidak mencukupi! Stok tersedia: {$stokTersedia} {$pemasukan->satuan}"
                );
            }

            // Simpan pengeluaran
            PengeluaranBahan::create([
                'tanggal_pengeluaran' => now(),
                'pemasukan_id' => $validated['pemasukan_id'],
                'kode_barang' => $pemasukan->kode_barang,
                'nama_barang' => $pemasukan->nama_barang,
                'kategori_barang' => $pemasukan->kategori_barang,
                'jumlah_dikeluarkan' => $validated['jumlah_dikeluarkan'],
                'satuan' => $pemasukan->satuan,
                'lokasi_tujuan' => $validated['lokasi_tujuan'],
                'nama_penerima' => $validated['nama_penerima'],
                'nama_petugas' => $validated['nama_petugas']
            ]);

            return redirect()->route('pengeluaran-barang.index')
                ->with('success', 'Pengeluaran barang berhasil dicatat');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('pengeluaran-barang.index')
                ->with('error', 'Anda tidak memiliki akses untuk mengedit data.');
        }

        $bahan = PengeluaranBahan::findOrFail($id);
        return view('panel.heavyobject.pengeluaran-barang.edit', compact('bahan'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('pengeluaran-barang.index')
                ->with('error', 'Anda tidak memiliki akses untuk mengupdate data.');
        }

        $request->validate([
            'tanggal_pengeluaran' => 'required|date',
            'nama_barang' => 'required',
            'kode_barang' => 'required',
            'jumlah_dikeluarkan' => 'required|numeric|min:1',
            'satuan' => 'required',
            'lokasi_tujuan' => 'required',
            'nama_penerima' => 'required',
            'nama_petugas' => 'required'
        ]);

        $bahan = PengeluaranBahan::findOrFail($id);
        $bahan->update($request->all());

        return redirect()->route('pengeluaran-barang.index')
            ->with('success', 'Data pengeluaran berhasil diupdate!');
    }

    public function destroy($id)
    {
        try {
            if (!auth()->user() || auth()->user()->role !== 'admin') {
                return redirect()->route('pengeluaran-barang.index')
                    ->with('error', 'Anda tidak memiliki akses untuk menghapus data.');
            }

            $pengeluaran = PengeluaranBahan::findOrFail($id);
            
            // Hapus pengeluaran tanpa perlu mengembalikan stok
            $pengeluaran->delete();

            return redirect()->route('pengeluaran-barang.index')
                ->with('success', 'Data pengeluaran berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('pengeluaran-barang.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}