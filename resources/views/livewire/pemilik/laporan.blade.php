<?php

use Livewire\Volt\Component;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PenjualanExport;

new #[Layout('layouts.app')] #[Title('Laporan Penjualan - Surya Parfum')] class extends Component {
    // Properti Filter
    public $tanggal_mulai;
    public $tanggal_akhir;
    public $bulan_dipilih;
    
    // Properti Metrik
    public $total_pendapatan = 0;
    public $total_transaksi = 0;
    public $rata_rata_transaksi = 0;
    public $total_laba = 0;
    public $riwayat_penjualan = [];

    // Properti Rekap Barang
    public $summary_parfum_jadi = [];
    public $summary_bibit = [];
    public $summary_botol = [];

    public function mount()
    {
        $this->tanggal_mulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = Carbon::now()->format('Y-m-d');
        $this->bulan_dipilih  = Carbon::now()->format('Y-m'); 
    
        $this->filterLaporan();
    }

    public function terapkanBulan()
    {
        $this->validate(['bulan_dipilih' => 'required']);

        $this->tanggal_mulai = Carbon::parse($this->bulan_dipilih)->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = Carbon::parse($this->bulan_dipilih)->endOfMonth()->format('Y-m-d');
        $this->filterLaporan();
    }

    public function filterLaporan()
    {
        $this->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $mulai = Carbon::parse($this->tanggal_mulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggal_akhir)->endOfDay();

        $query = Penjualan::whereBetween('created_at', [$mulai, $akhir]);

        // Ambil Data Transaksi LUNAS untuk hitung Rekap & Metrik
        $penjualans_sukses = (clone $query)->where('status_pembayaran', 'success')
                                           ->with(['details.parfum', 'details.botol', 'details.parfumJadi'])
                                           ->get();
        
        $this->total_pendapatan = $penjualans_sukses->sum('total_bayar');
        $this->total_transaksi = $penjualans_sukses->count();
        $this->rata_rata_transaksi = $this->total_transaksi > 0 ? $this->total_pendapatan / $this->total_transaksi : 0;
        
        $laba = 0;
        $rekap_jadi = [];
        $rekap_bibit = [];
        $rekap_botol = [];

        // ALGORITMA PENGELOMPOKAN REKAP BARANG (Mencegah Botol Terhitung Ganda)
        foreach ($penjualans_sukses as $trx) {
            $current_racikan = null;

            foreach ($trx->details as $detail) {
                // Hitung Laba
                $laba += ($detail->subtotal - ($detail->subtotal_modal ?? 0));

                if ($detail->parfumJadi) {
                    $id = $detail->id_parfum_jadi;
                    if (!isset($rekap_jadi[$id])) {
                        $rekap_jadi[$id] = ['nama' => $detail->parfumJadi->nama_parfum, 'total_qty' => 0];
                    }
                    $rekap_jadi[$id]['total_qty'] += $detail->jumlah_pcs;
                    $current_racikan = null;

                } elseif ($detail->parfum) {
                    // Deteksi Harga Botol
                    $harga_bibit_normal = $detail->parfum->harga_jual_per_ml * $detail->jumlah_ml;
                    $selisih = $detail->subtotal - $harga_bibit_normal;
                    $indikasi_termasuk_botol = $selisih > 1;

                    // 1. Rekap Bibit (Selalu dijumlahkan)
                    $id_parfum = $detail->id_parfum;
                    if (!isset($rekap_bibit[$id_parfum])) {
                        $rekap_bibit[$id_parfum] = ['nama' => $detail->parfum->nama_parfum, 'total_ml' => 0];
                    }
                    $rekap_bibit[$id_parfum]['total_ml'] += $detail->jumlah_ml;

                    // 2. Rekap Botol (Hanya Dihitung Jika Memulai Racikan Baru)
                    $start_new = false;
                    if (!$current_racikan) {
                        $start_new = true;
                    } elseif ($indikasi_termasuk_botol) {
                        $start_new = true; // Ada harga botol baru = racikan baru
                    } elseif ($detail->id_botol !== $current_racikan['id_botol']) {
                        $start_new = true; // Beda botol = racikan baru
                    }

                    if ($start_new) {
                        $current_racikan = [
                            'id_botol' => $detail->id_botol,
                            'botol' => $detail->botol
                        ];

                        if ($detail->id_botol && $detail->botol) {
                            $id_botol = $detail->id_botol;
                            if (!isset($rekap_botol[$id_botol])) {
                                $rekap_botol[$id_botol] = ['nama' => $detail->botol->nama_botol, 'total_pcs' => 0];
                            }
                            $rekap_botol[$id_botol]['total_pcs'] += 1;
                        }
                    }
                }
            }
        }

        $this->total_laba = $laba;
        
        // Konversi ke Object untuk View
        $this->summary_parfum_jadi = collect($rekap_jadi)->map(fn($item) => (object) $item)->values()->all();
        $this->summary_bibit = collect($rekap_bibit)->map(fn($item) => (object) $item)->values()->all();
        $this->summary_botol = collect($rekap_botol)->map(fn($item) => (object) $item)->values()->all();

        // Ambil Daftar Semua Transaksi (termasuk pending/gagal) untuk di Tabel
        $this->riwayat_penjualan = (clone $query)->with(['pengguna', 'pelanggan', 'details.parfum', 'details.botol', 'details.parfumJadi'])->latest()->get();
    }

    public function setHariIni()
    {
        $this->tanggal_mulai = Carbon::now()->format('Y-m-d');
        $this->tanggal_akhir = Carbon::now()->format('Y-m-d');
        $this->filterLaporan();
    }
    
    public function exportExcel()
    {
        $nama_file = 'Laporan_Surya_Parfum_' . date('Ymd_His') . '.xlsx'; 

        return Excel::download(
            new PenjualanExport($this->tanggal_mulai, $this->tanggal_akhir), 
            $nama_file
        );
    }
}; ?>

<div x-data="{ showItemModal: false }">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-2xl font-bold dark:text-gray-100">Dasbor Laporan Penjualan</h2>
            <div class="text-right">
                <p class="text-sm text-gray-500">Pemilik Aktif:</p>
                <p class="font-semibold dark:text-gray-100">{{ auth()->user()->name }}</p>
            </div>
        </div>

       <div class="bg-white dark:bg-zinc-800 p-3 rounded-lg border dark:border-zinc-700 shadow-sm flex flex-col md:flex-row gap-4 items-end justify-between">
            <div class="flex flex-col md:flex-row gap-2 items-end w-full md:w-auto">
                <div>
                    <label class="block text-xs font-medium mb-1 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" wire:model="tanggal_mulai" class="text-sm border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-md px-2.5 py-1.5 w-full md:w-36 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" wire:model="tanggal_akhir" class="text-sm border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-md px-2.5 py-1.5 w-full md:w-36 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div class="flex gap-1.5">
                    <button wire:click="filterLaporan" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-1.5 px-3 rounded-md shadow-sm transition-all flex items-center gap-1">
                        🔍 Cari
                    </button>
                    <button wire:click="setHariIni" class="bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white text-sm font-semibold py-1.5 px-3 rounded-md shadow-sm transition-all">
                        Hari Ini
                    </button>
                </div>
            </div>

            <div class="hidden md:block w-px h-10 bg-gray-200 dark:bg-zinc-700"></div>
            <div class="block md:hidden w-full h-px bg-gray-200 dark:bg-zinc-700 my-1"></div>

            <div class="flex flex-col md:flex-row gap-2 items-end w-full md:w-auto">
                <div>
                    <label class="block text-xs font-medium mb-1 dark:text-gray-300">Bulan & Tahun</label>
                    <input type="month" wire:model="bulan_dipilih" class="text-sm border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-md px-2.5 py-1.5 w-full md:w-40 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div class="flex gap-1.5">
                    <button wire:click="terapkanBulan" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-1.5 px-3 rounded-md shadow-sm transition-all flex items-center gap-1" title="Tampilkan data bulan ini">
                        📅 Tampilkan
                    </button>
                    <button wire:click="exportExcel" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-1.5 px-3 rounded-md shadow-sm transition-all flex items-center gap-1" title="Export Excel sesuai rentang tanggal">
                        📊 Export
                    </button>
                </div>
            </div>
        </div>
        
        @error('tanggal_akhir')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror

        <div class="mb-2 border-b dark:border-zinc-700 pb-2">
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                Menampilkan data dari <strong class="dark:text-white">{{ \Carbon\Carbon::parse($tanggal_mulai)->format('d M Y') }}</strong> hingga <strong class="dark:text-white">{{ \Carbon\Carbon::parse($tanggal_akhir)->format('d M Y') }}</strong>.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 rounded-lg text-white shadow-md">
                <h3 class="text-green-100 text-sm font-medium mb-1">Total Pendapatan</h3>
                <p class="text-3xl font-bold">Rp {{ number_format($total_pendapatan, 0, ',', '.') }}</p>
            </div>
            
            <div @click="showItemModal = true" class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-lg text-white shadow-md cursor-pointer hover:scale-105 transition-transform duration-200 relative group overflow-hidden">
                <h3 class="text-blue-100 text-sm font-medium mb-1">Total Transaksi (Nota)</h3>
                <p class="text-3xl font-bold">{{ $total_transaksi }} <span class="text-lg font-normal">Kali</span></p>
                <div class="absolute bottom-2 right-3 text-blue-200 opacity-0 group-hover:opacity-100 transition-opacity flex items-center text-xs gap-1 font-semibold">
                    <span>Lihat Rekap Barang</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-lg text-white shadow-md">
                <h3 class="text-purple-100 text-sm font-medium mb-1">Rata-Rata per Transaksi</h3>
                <p class="text-3xl font-bold">Rp {{ number_format($rata_rata_transaksi, 0, ',', '.') }}</p>
            </div>
        </div>
        
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <flux:icon.banknotes class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Laba Kotor</p>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Rp {{ number_format($total_laba, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm mt-2">
            <h3 class="font-bold mb-4 dark:text-gray-100 text-lg border-b pb-2">Riwayat Nota / Struk</h3>
            <div class="overflow-x-auto h-[500px] overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 bg-zinc-100 dark:bg-zinc-900 shadow-sm z-10">
                        <tr class="border-b dark:border-zinc-700 dark:text-gray-200">
                            <th class="px-4 py-3 text-left">Tanggal & Waktu</th>
                            <th class="px-4 py-3 text-left">No. Faktur</th>
                            <th class="px-4 py-3 text-left">Kasir</th>
                            <th class="px-4 py-3 text-left">Pelanggan</th>
                            <th class="px-4 py-3 text-left w-72">Item Terjual</th>
                            <th class="px-4 py-3 text-left">Metode</th>
                            <th class="px-4 py-3 text-center">Status</th> 
                            <th class="px-4 py-3 text-right">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($riwayat_penjualan as $trx)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 dark:text-gray-300">
                            <td class="px-4 py-3 align-top">{{ \Carbon\Carbon::parse($trx->waktu_transaksi)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 align-top font-semibold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('kasir.struk', $trx->no_faktur) }}" target="_blank" title="Cetak Ulang Struk">
                                    {{ $trx->no_faktur }}
                                </a>
                            </td>
                            <td class="px-4 py-3 align-top">{{ $trx->pengguna->name }}</td>
                            <td class="px-4 py-3 align-top">{{ $trx->pelanggan ? $trx->pelanggan->nama_pelanggan : 'Umum' }}</td>
                            
                            <td class="px-4 py-3 align-top">
                                @php
                                    // ALGORITMA PENGELOMPOKAN RACIKAN UNTUK TABEL
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
                                @endphp

                                <ul class="list-none space-y-2">
                                    @foreach($grouped_items as $item)
                                        @if($item['type'] === 'jadi')
                                            @php $detail = $item['data']; @endphp
                                            <li class="border-b border-zinc-100 dark:border-zinc-700 pb-2 last:border-0 last:pb-0">
                                                <div class="flex flex-col">
                                                    <span class="font-semibold text-zinc-800 dark:text-zinc-300">[Ready] {{ $detail->parfumJadi->nama_parfum }}</span>
                                                    <div class="flex justify-between items-center mt-1 text-xs">
                                                        <span class="text-zinc-600 dark:text-zinc-400">{{ $detail->jumlah_pcs }} pcs @ Rp {{ number_format($detail->harga_saat_transaksi, 0, ',', '.') }}</span>
                                                        <span class="font-bold text-zinc-900 dark:text-zinc-200">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        @elseif($item['type'] === 'racikan')
                                            @php $racikan = $item['data']; @endphp
                                            <li class="border-b border-zinc-100 dark:border-zinc-700 pb-2 last:border-0 last:pb-0">
                                                <div class="flex flex-col">
                                                    <span class="font-semibold text-zinc-800 dark:text-zinc-300">
                                                        Racikan Custom
                                                    </span>
                                                    
                                                    <div class="flex flex-col text-xs mt-1 space-y-0.5">
                                                        @foreach($racikan['bibits'] as $bibit)
                                                            <span class="text-zinc-600 dark:text-zinc-400">
                                                            {{ $bibit['nama'] }} ({{ $bibit['ml'] }} ml)
                                                            <span class="text-zinc-500 dark:text-zinc-500">
                                                                Rp {{ number_format($bibit['harga'], 0, ',', '.') }}
                                                            </span>
                                                        </span>
                                                        @endforeach
                                                        
                                                        @if($racikan['is_bawa_sendiri'])
                                                            <span class="text-zinc-500 italic">Bawa Botol Sendiri</span>
                                                        @else
                                                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{ $racikan['botol']->nama_botol }} <span class="font-bold">(Rp {{ number_format($racikan['harga_botol'], 0, ',', '.') }})</span></span>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="flex justify-between items-center mt-1 pt-1 border-t border-dashed border-zinc-200 dark:border-zinc-700">
                                                        <span class="text-xs text-zinc-500 font-medium">Subtotal:</span>
                                                        <span class="font-bold text-zinc-900 dark:text-zinc-200">Rp {{ number_format($racikan['subtotal'], 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>

                                @if($trx->potongan_diskon > 0)
                                    <div class="mt-3 pt-2 border-t border-dashed border-zinc-300 dark:border-zinc-600 text-xs">
                                        <div class="flex justify-between text-zinc-500 dark:text-zinc-400 mb-0.5">
                                            <span>Subtotal:</span>
                                            <span>Rp {{ number_format($trx->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-orange-600 dark:text-orange-400 font-bold">
                                            <span>Diskon/Promo:</span>
                                            <span>- Rp {{ number_format($trx->potongan_diskon, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $trx->metode_pembayaran }}</span>
                            </td>
                            
                            <td class="px-4 py-3 align-top text-center">
                                @if($trx->status_pembayaran === 'success')
                                    <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded text-xs font-bold border border-green-200 dark:border-green-800">Lunas</span>
                                @elseif($trx->status_pembayaran === 'pending')
                                    <span class="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-1 rounded text-xs font-bold border border-yellow-200 dark:border-yellow-800">Pending</span>
                                @else
                                    <span class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-1 rounded text-xs font-bold border border-red-200 dark:border-red-800">Gagal</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-right font-bold text-green-600 dark:text-green-400">
                                Rp {{ number_format($trx->total_bayar, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($riwayat_penjualan) == 0)
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 italic">Tidak ada transaksi pada rentang tanggal tersebut.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div x-show="showItemModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="showItemModal" x-transition.opacity class="fixed inset-0 bg-gray-900/75 dark:bg-gray-950/80 transition-opacity backdrop-blur-sm" @click="showItemModal = false"></div>

        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <div x-show="showItemModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white dark:bg-zinc-900 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full border dark:border-zinc-700">
                
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center border-b dark:border-zinc-700 pb-3 mb-4">
                        <div>
                            <h3 class="text-xl leading-6 font-bold text-gray-900 dark:text-gray-100" id="modal-title">
                                Rekap Barang Terjual
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total rekapitulasi item yang laku dari tanggal <strong>{{ \Carbon\Carbon::parse($tanggal_mulai)->format('d M') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($tanggal_akhir)->format('d M Y') }}</strong></p>
                        </div>
                        <button @click="showItemModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors focus:outline-none p-1 bg-gray-100 dark:bg-zinc-800 rounded-lg">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-h-[60vh] overflow-y-auto p-1">
                        
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border dark:border-zinc-700/50">
                            <h4 class="font-bold text-blue-600 dark:text-blue-400 border-b dark:border-zinc-700 pb-2 mb-3 flex items-center gap-2">
                                📦 Parfum Jadi
                            </h4>
                            <ul class="text-sm space-y-2">
                                @forelse($summary_parfum_jadi as $item)
                                    <li class="flex justify-between items-center border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-2">
                                        <span class="text-gray-700 dark:text-gray-300 w-3/4">{{ $item->nama }}</span>
                                        <span class="font-bold text-gray-900 dark:text-white bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded text-xs">{{ $item->total_qty }} pcs</span>
                                    </li>
                                @empty
                                    <li class="text-gray-400 text-xs italic text-center py-4">Belum ada parfum jadi terjual</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border dark:border-zinc-700/50">
                            <h4 class="font-bold text-emerald-600 dark:text-emerald-400 border-b dark:border-zinc-700 pb-2 mb-3 flex items-center gap-2">
                                💧 Bibit (Racikan)
                            </h4>
                            <ul class="text-sm space-y-2">
                                @forelse($summary_bibit as $item)
                                    <li class="flex justify-between items-center border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-2">
                                        <span class="text-gray-700 dark:text-gray-300 w-3/4">{{ $item->nama }}</span>
                                        <span class="font-bold text-gray-900 dark:text-white bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded text-xs">{{ $item->total_ml }} ml</span>
                                    </li>
                                @empty
                                    <li class="text-gray-400 text-xs italic text-center py-4">Belum ada bibit terjual</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border dark:border-zinc-700/50">
                            <h4 class="font-bold text-purple-600 dark:text-purple-400 border-b dark:border-zinc-700 pb-2 mb-3 flex items-center gap-2">
                                🍾 Botol Terpakai
                            </h4>
                            <ul class="text-sm space-y-2">
                                @forelse($summary_botol as $item)
                                    <li class="flex justify-between items-center border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-2">
                                        <span class="text-gray-700 dark:text-gray-300 w-3/4">{{ $item->nama }}</span>
                                        <span class="font-bold text-gray-900 dark:text-white bg-purple-100 dark:bg-purple-900/40 px-2 py-0.5 rounded text-xs">{{ $item->total_pcs }} pcs</span>
                                    </li>
                                @empty
                                    <li class="text-gray-400 text-xs italic text-center py-4">Belum ada botol terpakai</li>
                                @endforelse
                            </ul>
                        </div>

                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-zinc-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t dark:border-zinc-700">
                    <button type="button" @click="showItemModal = false" class="w-full inline-flex justify-center rounded-lg shadow-sm px-6 py-2 bg-zinc-800 dark:bg-zinc-200 text-base font-semibold text-white dark:text-zinc-900 hover:bg-zinc-700 dark:hover:bg-white focus:outline-none sm:w-auto sm:text-sm transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>