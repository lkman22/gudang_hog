<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PemasukanBarangExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Supplier',
            'No PO',
            'Nama Barang',
            'Kode',
            'Kategori',
            'Jumlah Masuk',
            'Sisa Stok',
            'Satuan',
            'Kondisi',
            'Lokasi',
            'Petugas',
            'Catatan'
        ];
    }

    public function map($pemasukan): array
    {
        return [
            $pemasukan->tanggal_penerimaan->format('d/m/Y'),
            $pemasukan->nama_supplier,
            $pemasukan->nomor_po,
            $pemasukan->nama_barang,
            $pemasukan->kode_barang,
            $pemasukan->kategori_barang,
            $pemasukan->jumlah_diterima,
            $pemasukan->sisa_stok,
            $pemasukan->satuan,
            $pemasukan->kondisi_barang,
            $pemasukan->lokasi_penyimpanan,
            $pemasukan->nama_petugas,
            $pemasukan->note
        ];
    }
}