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

    public function __construct($tanggal_mulai, $tanggal_akhir)
    {
        $this->tanggal_mulai = $tanggal_mulai;
        $this->tanggal_akhir = $tanggal_akhir;
    }

    public function array(): array
    {
        $mulai = Carbon::parse($this->tanggal_mulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggal_akhir)->endOfDay();

        $penjualan = Penjualan::with(['pengguna', 'pelanggan', 'details.parfum', 'details.botol', 'details.parfumJadi'])
                        ->whereBetween('created_at', [$mulai, $akhir])
                        ->oldest()->get();

        $periode = $mulai->format('d M Y') . ' s/d ' . $akhir->format('d M Y');

        $data = [
            ['LAPORAN PENJUALAN — SURYA PARFUM'], 
            ['Periode: ' . $periode],             
            [''], 
            ['No', 'Tanggal & Waktu', 'No. Faktur', 'Kasir', 'Pelanggan', 'Item Terjual & Rincian', 'Metode', 'Status', 'Total Belanja'] 
        ];

        $no = 1;
        $total_pendapatan = 0;

        foreach ($penjualan as $trx) {
            $item_baris = [];
            $urutan = 1;

            // ALGORITMA PENGELOMPOKAN RACIKAN
            $grouped_items = [];
            $current_racikan = null;

            foreach ($trx->details as $detail) {
                if ($detail->parfumJadi) {
                    if ($current_racikan) {
                        $grouped_items[] = ['type' => 'racikan', 'data' => $current_racikan];
                        $current_racikan = null;
                    }
                    $grouped_items[] = ['type' => 'jadi', 'data' => $detail];
                } elseif ($detail->parfum) {
                    $is_bawa_sendiri = empty($detail->id_botol);
                    
                    $harga_bibit_normal = $detail->parfum->harga_jual_per_ml * $detail->jumlah_ml;
                    $selisih = $detail->subtotal - $harga_bibit_normal;
                    $indikasi_termasuk_botol = $selisih > 1;

                    $start_new = false;
                    if (!$current_racikan) {
                        $start_new = true;
                    } elseif ($indikasi_termasuk_botol) {
                        $start_new = true;
                    } elseif ($detail->id_botol !== $current_racikan['id_botol']) {
                        $start_new = true;
                    }

                    if ($start_new) {
                        if ($current_racikan) {
                            $grouped_items[] = ['type' => 'racikan', 'data' => $current_racikan];
                        }
                        $current_racikan = [
                            'id_botol' => $detail->id_botol,
                            'botol' => $detail->botol,
                            'is_bawa_sendiri' => $is_bawa_sendiri,
                            'harga_botol' => $indikasi_termasuk_botol ? $selisih : 0,
                            'bibits' => [],
                            'subtotal' => 0
                        ];
                    }

                    $current_racikan['bibits'][] = [
                        'nama' => $detail->parfum->nama_parfum,
                        'ml' => $detail->jumlah_ml,
                        'harga' => $harga_bibit_normal,
                    ];
                    $current_racikan['subtotal'] += $detail->subtotal;
                }
            }
            if ($current_racikan) {
                $grouped_items[] = ['type' => 'racikan', 'data' => $current_racikan];
            }

            // MENGUBAH GROUP MENJADI TEKS EXCEL
            foreach ($grouped_items as $item) {
                if ($item['type'] === 'jadi') {
                    $detail = $item['data'];
                    $item_baris[] = $urutan . '. [Ready] ' . $detail->parfumJadi->nama_parfum . "\n"
                                  . '    ' . $detail->jumlah_pcs . ' pcs  x  Rp ' . number_format($detail->harga_saat_transaksi, 0, ',', '.') . "\n"
                                  . '    Subtotal: Rp ' . number_format($detail->subtotal, 0, ',', '.');
                } elseif ($item['type'] === 'racikan') {
                    $racikan = $item['data'];
                    
                    if ($racikan['is_bawa_sendiri']) {
                        $botol_teks = 'Bawa Botol Sendiri (Refill)';
                    } else {
                        $botol_teks = $racikan['botol']->nama_botol . ' (Rp ' . number_format($racikan['harga_botol'], 0, ',', '.') . ')';
                    }

                    $bibit_teks = "";
                    foreach ($racikan['bibits'] as $b) {
                        $bibit_teks .= '    - Bibit ' . $b['nama'] . ' (' . $b['ml'] . ' ml)  Rp ' . number_format($b['harga'], 0, ',', '.') . "\n";
                    }

                    $item_baris[] = $urutan . ". Racikan Custom:\n"
                                  . $bibit_teks
                                  . '    - ' . $botol_teks . "\n"
                                  . '    Subtotal: Rp ' . number_format($racikan['subtotal'], 0, ',', '.');
                }
                $urutan++;
            }

            $items = implode("\n\n", $item_baris);

            if ($trx->potongan_diskon > 0) {
                $items .= "\n----------------------------------\n";
                $items .= "Subtotal : Rp " . number_format($trx->subtotal, 0, ',', '.') . "\n";
                $items .= "Diskon   : -Rp " . number_format($trx->potongan_diskon, 0, ',', '.');
            }

            $status_label = match($trx->status_pembayaran) {
                'success' => 'Lunas',
                'pending' => 'Pending',
                default   => 'Gagal',
            };

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

        $data[] = ['']; 
        $data[] = ['', '', '', '', '', '', '', 'TOTAL PENDAPATAN', $total_pendapatan];

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   
            'B' => 18,  
            'C' => 15,  
            'D' => 15,  
            'E' => 20,  
            'F' => 50,  
            'G' => 15,  
            'H' => 10,  
            'I' => 18,  
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $barisTerakhir = $sheet->getHighestRow();

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');

        $sheet->getStyle('A1:I2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A4:I' . $barisTerakhir)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('F')->getAlignment()->setWrapText(true);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);

        $sheet->getStyle('H' . $barisTerakhir . ':I' . $barisTerakhir)->getFont()->setBold(true);
        $sheet->getStyle('I5:I' . $barisTerakhir)->getNumberFormat()->setFormatCode('#,##0');
    }
}