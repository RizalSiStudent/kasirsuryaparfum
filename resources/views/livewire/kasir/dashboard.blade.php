<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Penjualan;
use Carbon\Carbon;

new #[Layout('layouts.app')] #[Title('Dashboard Kasir - Surya Parfum')] class extends Component {
    
    public $pendapatan_hari_ini = 0;
    public $transaksi_hari_ini = 0;
    public $transaksi_terakhir = [];

    public function mount()
    {
        $hariIni = Carbon::today();
        $userId = auth()->user()->id;

        // Ambil metrik khusus HARI INI dan KHUSUS kasir yang sedang login
        $penjualanHariIni = Penjualan::where('id_pengguna', $userId)
                                     ->whereDate('created_at', $hariIni)
                                     ->where('status_pembayaran', 'success');

        $this->pendapatan_hari_ini = (clone $penjualanHariIni)->sum('total_bayar');
        $this->transaksi_hari_ini = (clone $penjualanHariIni)->count();

        // Ambil 5 transaksi terakhir yang dilayani kasir ini
        $this->transaksi_terakhir = Penjualan::with('pelanggan')
                                     ->where('id_pengguna', $userId)
                                     ->latest()
                                     ->take(5)
                                     ->get();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
            <div>
                <h2 class="text-2xl font-bold dark:text-gray-100">Halo, {{ auth()->user()->name }}! 👋</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Selamat bekerja! Ini adalah ringkasan shift kamu hari ini.</p>
            </div>
            <a href="{{ route('kasir.penjualan') }}" wire:navigate class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-sm transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Buka Mesin Kasir
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between border-l-4 border-l-green-500">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Setoran Tunai & Non-Tunai Hari Ini</p>
                    <h3 class="text-3xl font-black text-green-600 dark:text-green-500 mt-1">Rp {{ number_format($pendapatan_hari_ini, 0, ',', '.') }}</h3>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pelanggan Dilayani Hari Ini</p>
                    <h3 class="text-3xl font-black text-blue-600 dark:text-blue-500 mt-1">{{ $transaksi_hari_ini }} Struk</h3>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm mt-2">
            <h3 class="font-bold text-lg dark:text-gray-100 mb-4 border-b dark:border-zinc-700 pb-2">5 Transaksi Terakhir Kamu</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-900 border-b dark:border-zinc-700 dark:text-gray-200">
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">No. Faktur</th>
                            <th class="px-4 py-3 text-left">Pelanggan</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($transaksi_terakhir as $trx)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 dark:text-gray-300">
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($trx->waktu_transaksi)->format('H:i') }} WIB</td>
                            <td class="px-4 py-3 font-semibold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('kasir.struk', $trx->no_faktur) }}" target="_blank" title="Cetak Ulang Struk">{{ $trx->no_faktur }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $trx->pelanggan ? $trx->pelanggan->nama_pelanggan : 'Umum' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($trx->status_pembayaran === 'success')
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Lunas</span>
                                @elseif($trx->status_pembayaran === 'pending')
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">Pending</span>
                                @else
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">Gagal</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold">Rp {{ number_format($trx->total_bayar, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        
                        @if(count($transaksi_terakhir) == 0)
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">Kamu belum mencatat transaksi apapun.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>