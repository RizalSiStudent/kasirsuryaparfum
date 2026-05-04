<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Parfum;
use App\Models\Botol;
use App\Models\ParfumJadi;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Kelola Stok & Retur - Surya Parfum')] class extends Component {
    use WithPagination;

    public $parfums, $botols, $parfum_jadis;
    public $search = '';

    // State Bibit
    public $id_parfum = '';
    public $qty_parfum = '';
    public $tipe_parfum = 'tambah';

    // State Botol
    public $id_botol = '';
    public $qty_botol = '';
    public $tipe_botol = 'tambah';

    // State Parfum Jadi
    public $id_parfum_jadi = '';
    public $qty_parfum_jadi = '';
    public $tipe_parfum_jadi = 'tambah';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Load data untuk pilihan di select
        $this->parfums = Parfum::orderBy('nama_parfum')->get();
        $this->botols = Botol::orderBy('nama_botol')->get();
        $this->parfum_jadis = ParfumJadi::orderBy('nama_parfum')->get();
    }

    // --- LOGIKA SIMPAN (Tetap Sama) ---
    public function simpanStokParfum()
    {
        $this->validate(['id_parfum' => 'required', 'qty_parfum' => 'required|numeric|min:1']);
        $item = Parfum::find($this->id_parfum);
        if ($this->tipe_parfum == 'tambah') {
            $item->increment('stok_ml', $this->qty_parfum);
            session()->flash('success_parfum', "Stok {$item->nama_parfum} ditambah!");
        } else {
            if ($item->stok_ml < $this->qty_parfum) { session()->flash('error_parfum', 'Stok kurang!'); return; }
            $item->decrement('stok_ml', $this->qty_parfum);
            session()->flash('success_parfum', "Stok {$item->nama_parfum} diretur!");
        }
        $this->reset(['id_parfum', 'qty_parfum']);
        $this->loadData();
    }

    public function simpanStokBotol()
    {
        $this->validate(['id_botol' => 'required', 'qty_botol' => 'required|numeric|min:1']);
        $item = Botol::find($this->id_botol);
        if ($this->tipe_botol == 'tambah') {
            $item->increment('stok_pcs', $this->qty_botol);
            session()->flash('success_botol', "Stok {$item->nama_botol} ditambah!");
        } else {
            if ($item->stok_pcs < $this->qty_botol) { session()->flash('error_botol', 'Stok kurang!'); return; }
            $item->decrement('stok_pcs', $this->qty_botol);
            session()->flash('success_botol', "Stok {$item->nama_botol} diretur!");
        }
        $this->reset(['id_botol', 'qty_botol']);
        $this->loadData();
    }

    public function simpanStokParfumJadi()
    {
        $this->validate(['id_parfum_jadi' => 'required', 'qty_parfum_jadi' => 'required|numeric|min:1']);
        $item = ParfumJadi::find($this->id_parfum_jadi);
        if ($this->tipe_parfum_jadi == 'tambah') {
            $item->increment('stok_pcs', $this->qty_parfum_jadi);
            session()->flash('success_jadi', "Stok {$item->nama_parfum} ditambah!");
        } else {
            if ($item->stok_pcs < $this->qty_parfum_jadi) { session()->flash('error_jadi', 'Stok kurang!'); return; }
            $item->decrement('stok_pcs', $this->qty_parfum_jadi);
            session()->flash('success_jadi', "Stok {$item->nama_parfum} diretur!");
        }
        $this->reset(['id_parfum_jadi', 'qty_parfum_jadi']);
        $this->loadData();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="mb-4">
            <h2 class="text-2xl font-bold dark:text-gray-100">Manajemen Stok Barang</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Input penambahan stok atau retur barang di sini.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm border-t-4 border-t-blue-500">
                <h3 class="font-bold mb-4 flex items-center gap-2 dark:text-gray-100">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    Update Bibit
                </h3>
                @if (session()->has('success_parfum')) <div class="text-green-600 text-xs mb-2 bg-green-50 p-2 rounded border border-green-200">{{ session('success_parfum') }}</div> @endif
                <div class="space-y-3">
                    <select wire:model="id_parfum" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih Bibit --</option>
                        @foreach($parfums as $p) <option value="{{ $p->id_parfum }}">{{ $p->nama_parfum }}</option> @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select wire:model="tipe_parfum" class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                            <option value="tambah">➕ Tambah</option>
                            <option value="kurang">➖ Retur</option>
                        </select>
                        <input type="number" wire:model="qty_parfum" placeholder="ML" class="w-20 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                    </div>
                    <button wire:click="simpanStokParfum" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-sm transition-all">Simpan</button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm border-t-4 border-t-orange-500">
                <h3 class="font-bold mb-4 flex items-center gap-2 dark:text-gray-100">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Update Botol
                </h3>
                @if (session()->has('success_botol')) <div class="text-green-600 text-xs mb-2 bg-green-50 p-2 rounded border border-green-200">{{ session('success_botol') }}</div> @endif
                <div class="space-y-3">
                    <select wire:model="id_botol" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih Botol --</option>
                        @foreach($botols as $b) <option value="{{ $b->id_botol }}">{{ $b->nama_botol }} ({{ $b->kapasitas_ml }} ML)</option> @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select wire:model="tipe_botol" class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                            <option value="tambah">➕ Tambah</option>
                            <option value="kurang">➖ Retur</option>
                        </select>
                        <input type="number" wire:model="qty_botol" placeholder="Pcs" class="w-20 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                    </div>
                    <button wire:click="simpanStokBotol" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded-lg text-sm transition-all">Simpan</button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm border-t-4 border-t-purple-500">
                <h3 class="font-bold mb-4 flex items-center gap-2 dark:text-gray-100">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    Update Produk Jadi
                </h3>
                @if (session()->has('success_jadi')) <div class="text-green-600 text-xs mb-2 bg-green-50 p-2 rounded border border-green-200">{{ session('success_jadi') }}</div> @endif
                <div class="space-y-3">
                    <select wire:model="id_parfum_jadi" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($parfum_jadis as $pj) <option value="{{ $pj->id_parfum_jadi }}">{{ $pj->nama_parfum }}</option> @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select wire:model="tipe_parfum_jadi" class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                            <option value="tambah">➕ Tambah</option>
                            <option value="kurang">➖ Retur</option>
                        </select>
                        <input type="number" wire:model="qty_parfum_jadi" placeholder="Pcs" class="w-20 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                    </div>
                    <button wire:click="simpanStokParfumJadi" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg text-sm transition-all">Simpan</button>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm mt-2">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h3 class="font-bold text-lg dark:text-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Inventaris Stok Saat Ini
                </h3>
                <div class="relative w-full md:w-64">
                    <input type="text" wire:model.live="search" placeholder="Cari nama barang..." class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg pl-10 pr-4 py-2 text-sm">
                    <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-4">
                    <h4 class="font-bold text-blue-600 dark:text-blue-400 text-sm border-b dark:border-zinc-700 pb-2">BIBIT PARFUM (ML)</h4>
                    <div class="max-h-80 overflow-y-auto space-y-2 pr-2">
                        @foreach($parfums as $p)
                        @if($search == '' || str_contains(strtolower($p->nama_parfum), strtolower($search)))
                        <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg border dark:border-zinc-700">
                            <span class="text-xs dark:text-gray-300">{{ $p->nama_parfum }}</span>
                            <span class="font-bold text-sm {{ $p->stok_ml < 100 ? 'text-red-500' : 'text-blue-600 dark:text-blue-400' }}">{{ $p->stok_ml }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold text-orange-600 dark:text-orange-400 text-sm border-b dark:border-zinc-700 pb-2">BOTOL KEMASAN (PCS)</h4>
                    <div class="max-h-80 overflow-y-auto space-y-2 pr-2">
                        @foreach($botols as $b)
                        @if($search == '' || str_contains(strtolower($b->nama_botol . ' ' . $b->kapasitas_ml), strtolower($search)))
                        <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg border dark:border-zinc-700">
                            <span class="text-xs dark:text-gray-300">{{ $b->nama_botol }} ({{ $b->kapasitas_ml }} ML)</span>
                            <span class="font-bold text-sm {{ $b->stok_pcs < 10 ? 'text-red-500' : 'text-orange-600 dark:text-orange-400' }}">{{ $b->stok_pcs }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold text-purple-600 dark:text-purple-400 text-sm border-b dark:border-zinc-700 pb-2">PARFUM JADI (PCS)</h4>
                    <div class="max-h-80 overflow-y-auto space-y-2 pr-2">
                        @foreach($parfum_jadis as $pj)
                        @if($search == '' || str_contains(strtolower($pj->nama_parfum), strtolower($search)))
                        <div class="flex justify-between items-center p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg border dark:border-zinc-700">
                            <span class="text-xs dark:text-gray-300">{{ $pj->nama_parfum }}</span>
                            <span class="font-bold text-sm {{ $pj->stok_pcs < 10 ? 'text-red-500' : 'text-purple-600 dark:text-purple-400' }}">{{ $pj->stok_pcs }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>