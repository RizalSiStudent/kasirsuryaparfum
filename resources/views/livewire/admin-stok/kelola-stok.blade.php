<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Parfum;
use App\Models\Botol;
use App\Models\ParfumJadi;
use App\Models\RiwayatStok; 
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Kelola Stok & Retur - Surya Parfum')] class extends Component {
    use WithPagination;

    public $parfums, $botols, $parfum_jadis;
    public $search = '';

    public $id_parfum = ''; public $qty_parfum = ''; public $tipe_parfum = 'tambah'; public $keterangan_parfum = ''; 
    public $id_botol = ''; public $qty_botol = ''; public $tipe_botol = 'tambah'; public $keterangan_botol = '';
    public $id_parfum_jadi = ''; public $qty_parfum_jadi = ''; public $tipe_parfum_jadi = 'tambah'; public $keterangan_parfum_jadi = '';

    public function mount() {
        $this->loadData();
    }

    public function loadData() {
        $this->parfums = Parfum::with('supplier')->orderBy('nama_parfum')->get();
        $this->botols = Botol::orderBy('nama_botol')->get();
        $this->parfum_jadis = ParfumJadi::orderBy('nama_parfum')->get();
    }

    public function simpanStokParfum() {
        if (empty($this->id_parfum)) { session()->flash('error_parfum', '⚠️ Peringatan: Bibit parfum belum dipilih!'); return; }
        if (empty($this->qty_parfum) || $this->qty_parfum < 1) { session()->flash('error_parfum', '⚠️ Peringatan: Takaran minimal 1!'); return; }

        $item = Parfum::find($this->id_parfum);
        $pergerakan = 'Stok Masuk';

        if ($this->tipe_parfum == 'tambah') {
            $item->increment('stok_ml', $this->qty_parfum);
            session()->flash('success_parfum', "Stok {$item->nama_parfum} ditambah!");
        } else {
            if ($item->stok_ml < $this->qty_parfum) { session()->flash('error_parfum', '⚠️ Gagal: Sisa stok tidak cukup!'); return; }
            $item->decrement('stok_ml', $this->qty_parfum);
            $pergerakan = 'Retur Keluar';
            session()->flash('success_parfum', "Stok {$item->nama_parfum} diretur!");
        }

        RiwayatStok::create([
            'kategori' => 'Bibit Parfum',
            'id_parfum' => $item->id_parfum,
            'jenis_pergerakan' => $pergerakan,
            'jumlah' => $this->qty_parfum,
            'keterangan' => $this->keterangan_parfum ?: null,
        ]);

        $this->reset(['id_parfum', 'qty_parfum', 'keterangan_parfum']);
        $this->loadData();
    }

    public function simpanStokBotol() {
        if (empty($this->id_botol)) { session()->flash('error_botol', '⚠️ Peringatan: Wadah botol belum dipilih!'); return; }
        if (empty($this->qty_botol) || $this->qty_botol < 1) { session()->flash('error_botol', '⚠️ Peringatan: Jumlah minimal 1!'); return; }

        $item = Botol::find($this->id_botol);
        $pergerakan = 'Stok Masuk';

        if ($this->tipe_botol == 'tambah') {
            $item->increment('stok_pcs', $this->qty_botol);
            session()->flash('success_botol', "Stok {$item->nama_botol} ditambah!");
        } else {
            if ($item->stok_pcs < $this->qty_botol) { session()->flash('error_botol', '⚠️ Gagal: Sisa stok tidak cukup!'); return; }
            $item->decrement('stok_pcs', $this->qty_botol);
            $pergerakan = 'Retur Keluar';
            session()->flash('success_botol', "Stok {$item->nama_botol} diretur!");
        }

        RiwayatStok::create([
            'kategori' => 'Botol Kemasan',
            'id_botol' => $item->id_botol,
            'jenis_pergerakan' => $pergerakan,
            'jumlah' => $this->qty_botol,
            'keterangan' => $this->keterangan_botol ?: null,
        ]);

        $this->reset(['id_botol', 'qty_botol', 'keterangan_botol']);
        $this->loadData();
    }

    public function simpanStokParfumJadi() {
        if (empty($this->id_parfum_jadi)) { session()->flash('error_jadi', '⚠️ Peringatan: Parfum jadi belum dipilih!'); return; }
        if (empty($this->qty_parfum_jadi) || $this->qty_parfum_jadi < 1) { session()->flash('error_jadi', '⚠️ Peringatan: Jumlah minimal 1!'); return; }

        $item = ParfumJadi::find($this->id_parfum_jadi);
        $pergerakan = 'Stok Masuk';

        if ($this->tipe_parfum_jadi == 'tambah') {
            $item->increment('stok_pcs', $this->qty_parfum_jadi);
            session()->flash('success_jadi', "Stok {$item->nama_parfum} ditambah!");
        } else {
            if ($item->stok_pcs < $this->qty_parfum_jadi) { session()->flash('error_jadi', '⚠️ Gagal: Sisa stok tidak cukup!'); return; }
            $item->decrement('stok_pcs', $this->qty_parfum_jadi);
            $pergerakan = 'Retur Keluar';
            session()->flash('success_jadi', "Stok {$item->nama_parfum} diretur!");
        }

        RiwayatStok::create([
            'kategori' => 'Parfum Jadi',
            'id_parfum_jadi' => $item->id_parfum_jadi,
            'jenis_pergerakan' => $pergerakan,
            'jumlah' => $this->qty_parfum_jadi,
            'keterangan' => $this->keterangan_parfum_jadi ?: null,
        ]);

        $this->reset(['id_parfum_jadi', 'qty_parfum_jadi', 'keterangan_parfum_jadi']);
        $this->loadData();
    }

    public function with(): array {
        return [
            'riwayats' => RiwayatStok::with(['parfum', 'botol', 'parfumJadi'])->latest()->paginate(10)
        ];
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
                @if (session()->has('success_parfum')) <div class="text-green-700 text-xs mb-3 bg-green-50 p-2.5 rounded-lg border border-green-200 font-medium">{{ session('success_parfum') }}</div> @endif
                @if (session()->has('error_parfum')) <div class="text-red-700 text-xs mb-3 bg-red-50 p-2.5 rounded-lg border border-red-200 font-medium">{{ session('error_parfum') }}</div> @endif
                <div class="space-y-3">
                    <select wire:model="id_parfum" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Bibit --</option>
                        @foreach($parfums as $p) <option value="{{ $p->id_parfum }}">{{ $p->nama_parfum }}  [Supplier: {{ $p->supplier->nama_supplier ?? 'Umum' }}]</option> @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select wire:model="tipe_parfum" class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                            <option value="tambah">➕ Tambah</option>
                            <option value="kurang">➖ Retur</option>
                        </select>
                        <input type="number" wire:model="qty_parfum" placeholder="ML" class="w-20 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm text-center focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <input type="text" wire:model="keterangan_parfum" placeholder="Catatan/No. Nota (Opsional)" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <button wire:click="simpanStokParfum" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-sm transition-all shadow-sm">Simpan</button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm border-t-4 border-t-orange-500">
                <h3 class="font-bold mb-4 flex items-center gap-2 dark:text-gray-100">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Update Botol
                </h3>
                @if (session()->has('success_botol')) <div class="text-green-700 text-xs mb-3 bg-green-50 p-2.5 rounded-lg border border-green-200 font-medium">{{ session('success_botol') }}</div> @endif
                @if (session()->has('error_botol')) <div class="text-red-700 text-xs mb-3 bg-red-50 p-2.5 rounded-lg border border-red-200 font-medium">{{ session('error_botol') }}</div> @endif
                <div class="space-y-3">
                    <select wire:model="id_botol" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-orange-500 focus:border-orange-500">
                        <option value="">-- Pilih Botol --</option>
                        @foreach($botols as $b) <option value="{{ $b->id_botol }}">{{ $b->nama_botol }} ({{ $b->kapasitas_ml }} ML)</option> @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select wire:model="tipe_botol" class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                            <option value="tambah">➕ Tambah</option>
                            <option value="kurang">➖ Retur</option>
                        </select>
                        <input type="number" wire:model="qty_botol" placeholder="Pcs" class="w-20 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm text-center focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <input type="text" wire:model="keterangan_botol" placeholder="Catatan/No. Nota (Opsional)" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-orange-500 focus:border-orange-500">
                    <button wire:click="simpanStokBotol" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded-lg text-sm transition-all shadow-sm">Simpan</button>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm border-t-4 border-t-purple-500">
                <h3 class="font-bold mb-4 flex items-center gap-2 dark:text-gray-100">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    Update Produk Jadi
                </h3>
                @if (session()->has('success_jadi')) <div class="text-green-700 text-xs mb-3 bg-green-50 p-2.5 rounded-lg border border-green-200 font-medium">{{ session('success_jadi') }}</div> @endif
                @if (session()->has('error_jadi')) <div class="text-red-700 text-xs mb-3 bg-red-50 p-2.5 rounded-lg border border-red-200 font-medium">{{ session('error_jadi') }}</div> @endif
                <div class="space-y-3">
                    <select wire:model="id_parfum_jadi" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($parfum_jadis as $pj) <option value="{{ $pj->id_parfum_jadi }}">{{ $pj->nama_parfum }}</option> @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select wire:model="tipe_parfum_jadi" class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm">
                            <option value="tambah">➕ Tambah</option>
                            <option value="kurang">➖ Retur</option>
                        </select>
                        <input type="number" wire:model="qty_parfum_jadi" placeholder="Pcs" class="w-20 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm text-center focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <input type="text" wire:model="keterangan_parfum_jadi" placeholder="Catatan/No. Nota (Opsional)" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500">
                    <button wire:click="simpanStokParfumJadi" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg text-sm transition-all shadow-sm">Simpan</button>
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
                    <input type="text" wire:model.live="search" placeholder="Cari nama barang..." class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-4">
                    <h4 class="font-bold text-blue-600 dark:text-blue-400 text-sm border-b dark:border-zinc-700 pb-2">BIBIT PARFUM (ML)</h4>
                    <div class="max-h-80 overflow-y-auto space-y-2 pr-2">
                        @foreach($parfums as $p)
                        @if($search == '' || str_contains(strtolower($p->nama_parfum), strtolower($search)) || str_contains(strtolower($p->supplier->nama_supplier ?? ''), strtolower($search)))
                        <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border dark:border-zinc-700/50 hover:border-blue-300 dark:hover:border-blue-800 transition">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">{{ $p->nama_parfum }}</span>
                                <span class="text-[10px] text-zinc-500 mt-0.5">Supplier: {{ $p->supplier->nama_supplier ?? 'Umum/Lainnya' }}</span>
                            </div>
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
                        <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border dark:border-zinc-700/50 hover:border-orange-300 dark:hover:border-orange-800 transition">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">{{ $b->nama_botol }}</span>
                                <span class="text-[10px] text-zinc-500 mt-0.5">Kapasitas: {{ $b->kapasitas_ml }} ML</span>
                            </div>
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
                        <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border dark:border-zinc-700/50 hover:border-purple-300 dark:hover:border-purple-800 transition">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">{{ $pj->nama_parfum }}</span>
                            <span class="font-bold text-sm {{ $pj->stok_pcs < 10 ? 'text-red-500' : 'text-purple-600 dark:text-purple-400' }}">{{ $pj->stok_pcs }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl border dark:border-zinc-700 shadow-sm mt-2 overflow-hidden">
            <div class="p-6 flex flex-col gap-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="font-bold text-lg dark:text-gray-100">Log Seluruh Aktivitas Gudang</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-zinc-600 dark:text-zinc-400 border-b dark:border-zinc-700">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Nama Barang</th>
                                <th class="px-4 py-3 text-center">Aktivitas</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3">Keterangan / Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @forelse($riwayats as $log)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition text-zinc-800 dark:text-zinc-300">
                                <td class="px-4 py-3 text-xs whitespace-nowrap">{{ $log->created_at->format('d M Y, H:i') }}</td>
                                
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold border 
                                        {{ $log->kategori === 'Bibit Parfum' ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $log->kategori === 'Botol Kemasan' ? 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                        {{ $log->kategori === 'Parfum Jadi' ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                    ">
                                        {{ $log->kategori }}
                                    </span>
                                </td>
                                
                                <td class="px-4 py-3 font-medium">
                                    @if($log->kategori === 'Bibit Parfum') {{ $log->parfum->nama_parfum ?? 'Barang Terhapus' }}
                                    @elseif($log->kategori === 'Botol Kemasan') {{ $log->botol->nama_botol ?? 'Barang Terhapus' }}
                                    @elseif($log->kategori === 'Parfum Jadi') {{ $log->parfumJadi->nama_parfum ?? 'Barang Terhapus' }}
                                    @endif
                                </td>
                                
                                <td class="px-4 py-3 text-center">
                                    @if($log->jenis_pergerakan === 'Stok Masuk')
                                        <span class="text-green-600 dark:text-green-400 font-bold text-xs flex items-center justify-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg> Masuk</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400 font-bold text-xs flex items-center justify-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg> Retur</span>
                                    @endif
                                </td>
                                
                                <td class="px-4 py-3 text-center">
                                    <span class="font-black">{{ number_format($log->jumlah, 0, ',', '.') }}</span>
                                    <span class="text-[11px] font-normal text-zinc-500 dark:text-zinc-400 ml-0.5">
                                        {{ $log->kategori === 'Bibit Parfum' ? 'ML' : 'Pcs' }}
                                    </span>
                                </td>
                                
                                <td class="px-4 py-3 text-xs italic text-gray-500">{{ $log->keterangan ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-6 text-zinc-500 italic">Belum ada aktivitas terekam.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $riwayats->links() }}</div>
            </div>
        </div>

    </div>
</div>