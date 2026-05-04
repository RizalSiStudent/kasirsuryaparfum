<?php

use Livewire\Volt\Component;
use App\Models\ParfumJadi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Parfum Jadi - Surya Parfum')] class extends Component {
    public $parfum_jadis, $nama_parfum, $harga_beli_per_pcs, $harga_jual_per_pcs, $stok_pcs, $id_parfum_jadi;
    public $isOpen = false;

    public function mount()
    {
        $this->loadParfumJadis();
    }

    public function loadParfumJadis()
    {
        $this->parfum_jadis = ParfumJadi::all();
    }

    public function create()
    {
        $this->resetFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function resetFields()
    {
        $this->id_parfum_jadi = '';
        $this->nama_parfum = '';
        $this->harga_beli_per_pcs = '';
        $this->harga_jual_per_pcs = '';
        $this->stok_pcs = '';
    }

    public function store()
    {
        $this->validate([
            'nama_parfum' => 'required|string|max:100',
            'harga_beli_per_pcs' => 'required|numeric',
            'harga_jual_per_pcs' => 'required|numeric',
            'stok_pcs' => 'required|integer',
        ]);

        ParfumJadi::updateOrCreate(['id_parfum_jadi' => $this->id_parfum_jadi], [
            'nama_parfum' => $this->nama_parfum,
            'harga_beli_per_pcs' => $this->harga_beli_per_pcs,
            'harga_jual_per_pcs' => $this->harga_jual_per_pcs,
            'stok_pcs' => $this->stok_pcs,
        ]);

        session()->flash('message', $this->id_parfum_jadi ? 'Data Parfum Jadi Berhasil Diperbarui.' : 'Data Parfum Jadi Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
        $this->loadParfumJadis();
    }

    public function edit($id)
    {
        $parfum = ParfumJadi::findOrFail($id);
        $this->id_parfum_jadi = $id;
        $this->nama_parfum = $parfum->nama_parfum;
        $this->harga_beli_per_pcs = $parfum->harga_beli_per_pcs;
        $this->harga_jual_per_pcs = $parfum->harga_jual_per_pcs;
        $this->stok_pcs = $parfum->stok_pcs;
        
        $this->openModal();
    }

    public function delete($id)
    {
        ParfumJadi::find($id)->delete();
        session()->flash('message', 'Data Parfum Jadi Berhasil Dihapus.');
        $this->loadParfumJadis();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Kelola Data Parfum Jadi (Siap Jual)</h3>
                <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    + Tambah Parfum Jadi
                </button>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            @if($isOpen)
                <div class="mb-6 bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4">{{ $id_parfum_jadi ? 'Edit' : 'Tambah' }} Parfum Jadi</h3>
                    <form wire:submit.prevent="store">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Nama Parfum (Termasuk Ukuran)</label>
                                <input type="text" wire:model="nama_parfum" placeholder="Contoh: Surya Signature Baccarat 30ml" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_parfum') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Beli per Botol (Rp)</label>
                                <input type="number" step="0.01" wire:model="harga_beli_per_pcs" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_beli_per_pcs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Jual per Botol (Rp)</label>
                                <input type="number" step="0.01" wire:model="harga_jual_per_pcs" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_jual_per_pcs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Stok Awal (Botol/Pcs)</label>
                                <input type="number" wire:model="stok_pcs" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('stok_pcs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-sm transition-all">Simpan</button>
                            <button type="button" wire:click="closeModal" class="bg-zinc-500 hover:bg-zinc-600 text-white px-4 py-2 rounded-lg shadow-sm transition-all">Batal</button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border dark:border-zinc-700 shadow-sm">
                <table class="min-w-full bg-white dark:bg-zinc-900">
                    <thead>
                        <tr class="bg-zinc-100 dark:bg-zinc-800 border-b dark:border-zinc-700">
                            <th class="px-4 py-3 text-left font-semibold">Nama Produk</th>
                            <th class="px-4 py-3 text-left font-semibold">Harga Beli</th>
                            <th class="px-4 py-3 text-left font-semibold">Harga Jual</th>
                            <th class="px-4 py-3 text-center font-semibold">Stok (Pcs)</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($parfum_jadis as $pj)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $pj->nama_parfum }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($pj->harga_beli_per_pcs, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-green-600 dark:text-green-400 font-semibold">Rp {{ number_format($pj->harga_jual_per_pcs, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $pj->stok_pcs }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="edit({{ $pj->id_parfum_jadi }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $pj->id_parfum_jadi }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($parfum_jadis) == 0)
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Belum ada data parfum jadi.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>