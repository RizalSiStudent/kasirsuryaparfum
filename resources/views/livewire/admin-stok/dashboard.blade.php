<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Parfum;
use App\Models\ParfumJadi;
use App\Models\Botol;

new #[Layout('layouts.app')] #[Title('Dashboard Admin Stok - Surya Parfum')] class extends Component {
    
    // Metrik Ringkasan
    public $total_bibit = 0;
    public $total_parfum_jadi = 0;
    public $total_botol = 0;

    // Data Peringatan Stok
    public $stok_kritis = [];

    public function mount()
    {
        // 1. Hitung total macam barang
        $this->total_bibit = Parfum::count();
        $this->total_parfum_jadi = ParfumJadi::count();
        $this->total_botol = Botol::count();

        // 2. Ambil barang-barang yang stoknya kritis (mau habis)
        // Kriteria Kritis: Bibit < 100 ML, Botol & Parfum Jadi < 10 Pcs
        $bibit_kritis = Parfum::where('stok_ml', '<', 100)->get()->map(function($item) {
            return [
                'nama' => $item->nama_parfum, 
                'kategori' => 'Bibit Parfum', 
                'sisa_stok' => $item->stok_ml,
                'satuan' => 'ML'
            ];
        });

        $parfum_jadi_kritis = ParfumJadi::where('stok_pcs', '<', 10)->get()->map(function($item) {
            return [
                'nama' => $item->nama_parfum, 
                'kategori' => 'Parfum Jadi', 
                'sisa_stok' => $item->stok_pcs,
                'satuan' => 'Pcs'
            ];
        });

        $botol_kritis = Botol::where('stok_pcs', '<', 10)->get()->map(function($item) {
            return [
                'nama' => $item->nama_botol, 
                'kategori' => 'Botol Kemasan', 
                'sisa_stok' => $item->stok_pcs,
                'satuan' => 'Pcs'
            ];
        });

        // Gabungkan semua data kritis menjadi satu tabel peringatan
        $this->stok_kritis = collect($bibit_kritis)
                                ->merge($parfum_jadi_kritis)
                                ->merge($botol_kritis)
                                ->sortBy('sisa_stok') // Urutkan dari yang paling sedikit
                                ->all();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
            <div>
                <h2 class="text-2xl font-bold dark:text-gray-100">Halo, {{ auth()->user()->name }}! 📦</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pantau ketersediaan barang di gudang Surya Parfum dari sini.</p>
            </div>
            <a href="{{ route('admin-stok.kelola') }}" wire:navigate class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 px-6 rounded-xl shadow-sm transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Kelola Stok Barang
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center gap-4 border-l-4 border-l-blue-500">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full text-blue-600 dark:text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Varian Bibit Parfum</p>
                    <h3 class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1">{{ $total_bibit }} Item</h3>
                </div>~
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center gap-4 border-l-4 border-l-purple-500">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full text-purple-600 dark:text-purple-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Varian Parfum Jadi</p>
                    <h3 class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1">{{ $total_parfum_jadi }} Item</h3>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center gap-4 border-l-4 border-l-orange-500">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full text-orange-600 dark:text-orange-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Botol</p>
                    <h3 class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1">{{ $total_botol }} Item</h3>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-red-200 dark:border-red-900/50 shadow-sm mt-2">
            <h3 class="font-bold text-lg text-red-600 dark:text-red-400 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Peringatan: Stok Hampir Habis!
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-900/50 text-red-700 dark:text-red-300">
                            <th class="px-4 py-3 text-left">Nama Barang</th>
                            <th class="px-4 py-3 text-left">Kategori</th>
                            <th class="px-4 py-3 text-center">Sisa Stok</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100 dark:divide-red-900/30">
                        @foreach($stok_kritis as $item)
                        <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10 dark:text-gray-300">
                            <td class="px-4 py-3 font-semibold">{{ $item['nama'] }}</td>
                            <td class="px-4 py-3">
                                <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $item['kategori'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-red-600 dark:text-red-400 font-black text-base">{{ $item['sisa_stok'] }}</span> <span class="text-xs">{{ $item['satuan'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin-stok.kelola') }}" class="text-blue-600 hover:underline text-xs font-bold">Restock Sekarang</a>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($stok_kritis) == 0)
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-green-600 dark:text-green-400 font-medium">
                                👍 Bagus! Saat ini tidak ada barang yang stoknya kritis.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>