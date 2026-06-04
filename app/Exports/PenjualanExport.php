<?php

namespace App\Exports;

use App\Models\Penjualan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PenjualanExport implements FromArray, WithStyles, WithColumnWidths
{
    protected $tanggal_mulai;
    protected $tanggal_akhir;

    // Menerima parameter tanggal dari Livewire Component
    public function __construct($tanggal_mulai, $tanggal_akhir)
    {
        $this->tanggal_mulai = $tanggal_mulai;
        $this->tanggal_akhir = $tanggal_akhir;
    }

    public function array(): array
    {
        $mulai = Carbon::parse($this->tanggal_mulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggal_akhir)->endOfDay();

        // Ambil data dari database (diurutkan dari tanggal paling awal)
        $penjualan = Penjualan::with(['pengguna', 'pelanggan', 'details.parfum', 'details.botol', 'details.parfumJadi'])
                        ->whereBetween('created_at', [$mulai, $akhir])
                        ->oldest()->get();

        $periode = $mulai->format('d M Y') . ' s/d ' . $akhir->format('d M Y');

        // Susun struktur baris Excel secara manual
        $data = [
            ['LAPORAN PENJUALAN — SURYA PARFUM'], 
            ['Periode: ' . $periode],             
            [''], // <-- PERBAIKAN: Beri tanda petik agar tidak diabaikan Excel
            ['No', 'Tanggal & Waktu', 'No. Faktur', 'Kasir', 'Pelanggan', 'Item Terjual & Rincian', 'Metode', 'Status', 'Total Belanja'] 
        ];

        $no = 1;
        $total_pendapatan = 0;

        foreach ($penjualan as $trx) {
            $item_baris = [];
            $urutan = 1;

            foreach ($trx->details as $detail) {
                if ($detail->parfumJadi) {
                    $item_baris[] = $urutan . '. ' . $detail->parfumJadi->nama_parfum . "\n"
                                  . '    ' . $detail->jumlah_pcs . ' pcs  x  Rp ' . number_format($detail->harga_saat_transaksi, 0, ',', '.') 
                                  . '  =  Rp ' . number_format($detail->subtotal, 0, ',', '.');
                } elseif ($detail->parfum) {
                    $botol = $detail->botol ? $detail->botol->nama_botol : 'Bawa Botol Sendiri';
                    $item_baris[] = $urutan . '. Racikan: ' . $detail->parfum->nama_parfum . "\n"
                                  . '    ' . $detail->jumlah_ml . ' ml | ' . $botol . "\n"
                                  . '    Rp ' . number_format($detail->harga_saat_transaksi, 0, ',', '.') 
                                  . '  =  Rp ' . number_format($detail->subtotal, 0, ',', '.');
                }
                $urutan++;
            }

            // ... kode sebelumnya ...

            $items = implode("\n\n", $item_baris);

            // --- TAMBAHAN BARU: Info Diskon di Excel ---
            if ($trx->potongan_diskon > 0) {
                $items .= "\n----------------------------------\n";
                $items .= "Subtotal : Rp " . number_format($trx->subtotal, 0, ',', '.') . "\n";
                $items .= "Diskon   : -Rp " . number_format($trx->potongan_diskon, 0, ',', '.');
            }
            // -------------------------------------------

            $status_label = match($trx->status_pembayaran) {
                'success' => 'Lunas',
                'pending' => 'Pending',
                default   => 'Gagal',
            };

            // ... sisa kode di bawahnya ...

            $data[] = [
                $no++,
                Carbon::parse($trx->waktu_transaksi)->format('d/m/Y H:i'),
                $trx->no_faktur,
                $trx->pengguna->name ?? '-',
                $trx->pelanggan ? $trx->pelanggan->nama_pelanggan : 'Umum',
                $items,
                $trx->metode_pembayaran,
                $status_label,
                $trx->total_bayar
            ];

            if ($trx->status_pembayaran === 'success') {
                $total_pendapatan += $trx->total_bayar;
            }
        }

        $data[] = ['']; // <-- PERBAIKAN: Beri tanda petik juga di sini
        $data[] = ['', '', '', '', '', '', '', 'TOTAL PENDAPATAN', $total_pendapatan];

        return $data;
    }

    // Mengatur lebar masing-masing kolom
    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No (Sekarang bisa dibuat ramping kembali)
            'B' => 18,  // Tanggal
            'C' => 15,  // Faktur
            'D' => 15,  // Kasir
            'E' => 20,  // Pelanggan
            'F' => 45,  // Item Terjual
            'G' => 12,  // Metode
            'H' => 10,  // Status
            'I' => 15,  // Total
        ];
    }

    // Mengatur styling bawaan lembar kerja
    public function styles(Worksheet $sheet)
    {
        $barisTerakhir = $sheet->getHighestRow();

        // 1. Merge cell untuk Judul (Baris 1) dan Periode (Baris 2) dari Kolom A sampai I
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');

        // 2. Mengatur perataan tengah (Center) untuk Judul & Periode secara Horizontal & Vertikal
        $sheet->getStyle('A1:I2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // 3. Mengatur perataan vertikal ke Atas (Top) KHUSUS untuk Isi Tabel (Mulai Baris 4 ke bawah)
        $sheet->getStyle('A4:I' . $barisTerakhir)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        // 4. Mengaktifkan fitur Wrap Text otomatis untuk Kolom F (Item Terjual)
        $sheet->getStyle('F')->getAlignment()->setWrapText(true);

        // 5. Menebalkan font & mengatur ukuran teks Judul Utama serta Header Tabel
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);

        // 6. Menebalkan baris TOTAL PENDAPATAN di bagian akhir
        $sheet->getStyle('H' . $barisTerakhir . ':I' . $barisTerakhir)->getFont()->setBold(true);
    }
}