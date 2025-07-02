<?php

namespace App\Http\Controllers;

use App\Models\PemasukanBarang;
use App\Models\PengeluaranBahan;
use App\Models\DaftarBarang;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        // Hitung total kategori yang unik berdasarkan nama kategori
        $totalKategori = PemasukanBarang::select('kategori_barang')
            ->distinct()
            ->whereNotNull('kategori_barang')
            ->pluck('kategori_barang') // Ambil daftar kategori
            ->unique() // Pastikan benar-benar unik
            ->count(); // Hitung jumlahnya

        // Debug untuk melihat kategori apa saja yang ada
        $kategoris = PemasukanBarang::select('kategori_barang')
            ->distinct()
            ->whereNotNull('kategori_barang')
            ->pluck('kategori_barang');
        
        // Hitung total barang yang unik berdasarkan kode barang
        $totalBarang = PemasukanBarang::select('kode_barang')
            ->distinct()
            ->count();

        // Hitung total stok
        $totalStok = PemasukanBarang::sum('jumlah_diterima');

        // Hitung total pengeluaran
        $totalPengeluaran = PengeluaranBahan::sum('jumlah_dikeluarkan');

        // Tambahkan daftar kategori ke view untuk debugging
        return view('panel.dashboard', compact(
            'totalKategori',
            'totalBarang', 
            'totalStok',
            'totalPengeluaran',
            'kategoris' // Tambahkan ini untuk debugging
        ));
    }

    public function getChartData(Request $request)
    {
        $range = $request->get('range', 'year');
        $now = Carbon::now();
        
        switch($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                // Format per jam (00:00-23:00)
                $period = collect(range(0, 23))->map(function($hour) {
                    return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                })->toArray();
                $groupBy = 'HOUR(tanggal_penerimaan)';
                $groupByPengeluaran = 'HOUR(tanggal_pengeluaran)';
                break;

            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                // Format nama hari dalam bahasa Indonesia
                $period = collect(range(0, 6))->map(function($day) use ($startDate) {
                    return $startDate->copy()->addDays($day)->isoFormat('dddd');
                })->toArray();
                $groupBy = 'DAYOFWEEK(tanggal_penerimaan)';
                $groupByPengeluaran = 'DAYOFWEEK(tanggal_pengeluaran)';
                break;

            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                // Format tanggal (1-31)
                $period = collect(range(1, $now->daysInMonth))->map(function($day) use ($now) {
                    return $day . ' ' . $now->format('M');
                })->toArray();
                $groupBy = 'DAY(tanggal_penerimaan)';
                $groupByPengeluaran = 'DAY(tanggal_pengeluaran)';
                break;

            case 'quarter':
                $startDate = $now->copy()->subMonths(2)->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                // 3 bulan terakhir
                $period = collect(range(0, 2))->map(function($monthsAgo) use ($now) {
                    return $now->copy()->subMonths(2 - $monthsAgo)->format('M Y');
                })->toArray();
                $groupBy = 'DATE_FORMAT(tanggal_penerimaan, "%Y-%m")';
                $groupByPengeluaran = 'DATE_FORMAT(tanggal_pengeluaran, "%Y-%m")';
                break;

            case 'half':
                $startDate = $now->copy()->subMonths(5)->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                // 6 bulan terakhir
                $period = collect(range(0, 5))->map(function($monthsAgo) use ($now) {
                    return $now->copy()->subMonths(5 - $monthsAgo)->format('M Y');
                })->toArray();
                $groupBy = 'DATE_FORMAT(tanggal_penerimaan, "%Y-%m")';
                $groupByPengeluaran = 'DATE_FORMAT(tanggal_pengeluaran, "%Y-%m")';
                break;

            default: // year
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                // Bulan Januari-Desember tahun ini
                $period = collect(range(1, 12))->map(function($month) use ($now) {
                    return Carbon::create($now->year, $month, 1)->format('F');
                })->toArray();
                $groupBy = 'MONTH(tanggal_penerimaan)';
                $groupByPengeluaran = 'MONTH(tanggal_pengeluaran)';
                break;
        }

        // Query data pemasukan
        $pemasukan = PemasukanBarang::whereBetween('tanggal_penerimaan', [$startDate, $endDate])
            ->selectRaw("$groupBy as period, SUM(jumlah_diterima) as total")
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        // Query data pengeluaran
        $pengeluaran = PengeluaranBahan::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])
            ->selectRaw("$groupByPengeluaran as period, SUM(jumlah_dikeluarkan) as total")
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        // Inisialisasi array data dengan nilai 0
        $pemasukanData = array_fill(0, count($period), 0);
        $pengeluaranData = array_fill(0, count($period), 0);

        // Isi data pemasukan
        foreach($pemasukan as $key => $value) {
            $index = match($range) {
                'today' => (int)$key,
                'week' => ((int)$key + 5) % 7, // Konversi DAYOFWEEK MySQL ke index array
                'month' => ((int)$key - 1),
                'year' => ((int)$key - 1),
                default => array_search(Carbon::parse($key)->format($range === 'half' || $range === 'quarter' ? 'M Y' : 'F'), $period)
            };
            
            if($index !== false && isset($pemasukanData[$index])) {
                $pemasukanData[$index] = (int)$value;
            }
        }

        // Isi data pengeluaran
        foreach($pengeluaran as $key => $value) {
            $index = match($range) {
                'today' => (int)$key,
                'week' => ((int)$key + 5) % 7,
                'month' => ((int)$key - 1),
                'year' => ((int)$key - 1),
                default => array_search(Carbon::parse($key)->format($range === 'half' || $range === 'quarter' ? 'M Y' : 'F'), $period)
            };
            
            if($index !== false && isset($pengeluaranData[$index])) {
                $pengeluaranData[$index] = (int)$value;
            }
        }

        return response()->json([
            'labels' => $period,
            'pemasukan' => $pemasukanData,
            'pengeluaran' => $pengeluaranData
        ]);
    }
}
