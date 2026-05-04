<?php

use Livewire\Volt\Component;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Laporan Penjualan - Surya Parfum')] class extends Component {
    // Properti Filter
    public $tanggal_mulai;
    public $tanggal_akhir;
    
    // Properti Metrik
    public $total_pendapatan = 0;
    public $total_transaksi = 0;
    public $rata_rata_transaksi = 0;
    public $riwayat_penjualan = [];

    public function mount()
    {
        // Default saat halaman dibuka: Tampilkan data dari awal bulan sampai hari ini
        $this->tanggal_mulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = Carbon::now()->format('Y-m-d');
        
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

        // PERBAIKAN: Hitung Metrik Dinamis HANYA UNTUK TRANSAKSI SUKSES/LUNAS
        $this->total_pendapatan = (clone $query)->where('status_pembayaran', 'success')->sum('total_bayar');
        $this->total_transaksi = (clone $query)->where('status_pembayaran', 'success')->count();
        $this->rata_rata_transaksi = $this->total_transaksi > 0 ? $this->total_pendapatan / $this->total_transaksi : 0;

        // Ambil Daftar Transaksi (Tabel tetap menampilkan semua agar kelihatan mana yang gagal)
        $this->riwayat_penjualan = (clone $query)->with(['pengguna', 'pelanggan'])
                                                 ->latest()
                                                 ->get();
    }

    public function setHariIni()
    {
        $this->tanggal_mulai = Carbon::now()->format('Y-m-d');
        $this->tanggal_akhir = Carbon::now()->format('Y-m-d');
        $this->filterLaporan();
    }
    public function exportExcel()
    {
        // 1. Siapkan Nama File
        $nama_file = 'Laporan_Surya_Parfum_' . date('Ymd_His') . '.csv';

        // 2. Ambil Data dari Database
        $penjualan = Penjualan::with(['pengguna', 'pelanggan'])
                        ->whereBetween('created_at', [
                            Carbon::parse($this->tanggal_mulai)->startOfDay(), 
                            Carbon::parse($this->tanggal_akhir)->endOfDay()
                        ])->latest()->get();

        // 3. Gunakan Fitur Download Bawaan Laravel (Tanpa Library)
        return response()->streamDownload(function () use ($penjualan) {
            // Buka file virtual
            $file = fopen('php://output', 'w');
            
            // Tulis Judul Kolom (Header)
            fputcsv($file, ['No', 'Tanggal & Waktu', 'No. Faktur', 'Kasir', 'Pelanggan', 'Metode Pembayaran', 'Status', 'Total Belanja (Rp)']);
            
            // Tulis Isi Data
            $total_pendapatan = 0;
            $no = 1;
            
            foreach ($penjualan as $trx) {
                fputcsv($file, [
                    $no++,
                    \Carbon\Carbon::parse($trx->waktu_transaksi)->format('d/m/Y H:i'),
                    $trx->no_faktur,
                    $trx->pengguna->name,
                    $trx->pelanggan ? $trx->pelanggan->nama_pelanggan : 'Umum',
                    $trx->metode_pembayaran,
                    $trx->status_pembayaran,
                    $trx->total_bayar
                ]);
                
                // PERBAIKAN: Hanya tambahkan ke total pendapatan JIKA lunas
                if ($trx->status_pembayaran === 'success') {
                    $total_pendapatan += $trx->total_bayar;
                }
            }
            
            // Tulis Baris Total di Paling Bawah
            fputcsv($file, ['', '', '', '', '', '', 'TOTAL PENDAPATAN', $total_pendapatan]);
            
            fclose($file);
        }, $nama_file);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-2xl font-bold dark:text-gray-100">Dasbor Laporan Penjualan</h2>
            <div class="text-right">
                <p class="text-sm text-gray-500">Pemilik Aktif:</p>
                <p class="font-semibold dark:text-gray-100">{{ auth()->user()->name }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border dark:border-zinc-700 shadow-sm flex flex-col md:flex-row gap-4 items-end">
            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Dari Tanggal</label>
                <input type="date" wire:model="tanggal_mulai" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Sampai Tanggal</label>
                <input type="date" wire:model="tanggal_akhir" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2">
            </div>
            <div class="flex gap-2">
                <button wire:click="filterLaporan" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-sm transition-all">
                    🔍 Terapkan Filter
                </button>
                <button wire:click="setHariIni" class="bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    Hari Ini Saja
                </button>
                <button wire:click="exportExcel" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-sm transition-all flex items-center gap-2">
        📊 Export Excel
    </button>
            </div>
        </div>
        
        @error('tanggal_akhir') 
            <span class="text-red-500 text-sm -mt-4 block">{{ $message }}</span> 
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
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-lg text-white shadow-md">
                <h3 class="text-blue-100 text-sm font-medium mb-1">Total Transaksi (Nota)</h3>
                <p class="text-3xl font-bold">{{ $total_transaksi }} <span class="text-lg font-normal">Kali</span></p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-lg text-white shadow-md">
                <h3 class="text-purple-100 text-sm font-medium mb-1">Rata-Rata per Transaksi</h3>
                <p class="text-3xl font-bold">Rp {{ number_format($rata_rata_transaksi, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm mt-2">
            <h3 class="font-bold mb-4 dark:text-gray-100 text-lg border-b pb-2">Rincian Transaksi</h3>
            <div class="overflow-x-auto h-[500px] overflow-y-auto">
                <table class="min-w-full text-sm">
    <thead class="sticky top-0 bg-zinc-100 dark:bg-zinc-900 shadow-sm">
        <tr class="border-b dark:border-zinc-700 dark:text-gray-200">
            <th class="px-4 py-3 text-left">Tanggal & Waktu</th>
            <th class="px-4 py-3 text-left">No. Faktur</th>
            <th class="px-4 py-3 text-left">Kasir</th>
            <th class="px-4 py-3 text-left">Pelanggan</th>
            <th class="px-4 py-3 text-left">Metode</th>
            <th class="px-4 py-3 text-center">Status</th> <th class="px-4 py-3 text-right">Total Belanja</th>
        </tr>
    </thead>
    <tbody class="divide-y dark:divide-zinc-700">
        @foreach($riwayat_penjualan as $trx)
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 dark:text-gray-300">
            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($trx->waktu_transaksi)->format('d/m/Y H:i') }}</td>
            <td class="px-4 py-3 font-semibold text-blue-600 dark:text-blue-400">
                <a href="{{ route('kasir.struk', $trx->no_faktur) }}" target="_blank" title="Cetak Ulang Struk">
                    {{ $trx->no_faktur }}
                </a>
            </td>
            <td class="px-4 py-3">{{ $trx->pengguna->name }}</td>
            <td class="px-4 py-3">{{ $trx->pelanggan ? $trx->pelanggan->nama_pelanggan : 'Umum' }}</td>
            <td class="px-4 py-3">
                <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $trx->metode_pembayaran }}</span>
            </td>
            
            <td class="px-4 py-3 text-center">
                @if($trx->status_pembayaran === 'success')
                    <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded text-xs font-bold border border-green-200 dark:border-green-800">Lunas</span>
                @elseif($trx->status_pembayaran === 'pending')
                    <span class="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-1 rounded text-xs font-bold border border-yellow-200 dark:border-yellow-800">Pending</span>
                @else
                    <span class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-1 rounded text-xs font-bold border border-red-200 dark:border-red-800">Gagal</span>
                @endif
            </td>

            <td class="px-4 py-3 text-right font-bold text-green-600 dark:text-green-400">
                Rp {{ number_format($trx->total_bayar, 0, ',', '.') }}
            </td>
        </tr>
        @endforeach
        
        @if(count($riwayat_penjualan) == 0)
        <tr>
            <td colspan="7" class="px-4 py-8 text-center text-gray-500 italic">Tidak ada transaksi pada rentang tanggal tersebut.</td>
        </tr>
        @endif
    </tbody>
</table>
            </div>
        </div>

    </div>
</div>