<?php

use Livewire\Volt\Component;
use App\Models\Botol;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Botol - Surya Parfum')] class extends Component {
    use WithFileUploads;

    public $botols, $nama_botol, $kapasitas_ml, $harga_jual_per_pcs, $harga_beli_per_pcs, $stok_pcs, $foto_botol, $id_botol;
    public $isOpen = false;

    public function mount()
    {
        $this->loadBotols();
    }

    public function loadBotols()
    {
        $this->botols = Botol::all();
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
        $this->id_botol = '';
        $this->nama_botol = '';
        $this->kapasitas_ml = '';
        $this->harga_jual_per_pcs = '';
        $this->harga_beli_per_pcs = '';
        $this->stok_pcs = '';
        $this->foto_botol = '';
    }

    public function store()
    {
        $this->validate([
            'nama_botol' => 'required|string|max:100',
            'kapasitas_ml' => 'required|integer',
            'harga_jual_per_pcs' => 'required|numeric',
            'harga_beli_per_pcs' => 'required|numeric',
            'stok_pcs' => 'required|integer',
        ]);

        Botol::updateOrCreate(['id_botol' => $this->id_botol], [
            'nama_botol' => $this->nama_botol,
            'kapasitas_ml' => $this->kapasitas_ml,
            'harga_jual_per_pcs' => $this->harga_jual_per_pcs,
            'harga_beli_per_pcs' => $this->harga_beli_per_pcs,
            'stok_pcs' => $this->stok_pcs,
        ]);

        session()->flash('message', $this->id_botol ? 'Data Botol Berhasil Diperbarui.' : 'Data Botol Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
        $this->loadBotols();
    }

    public function edit($id)
    {
        $botol = Botol::findOrFail($id);
        $this->id_botol = $id;
        $this->nama_botol = $botol->nama_botol;
        $this->kapasitas_ml = $botol->kapasitas_ml;
        $this->harga_jual_per_pcs = $botol->harga_jual_per_pcs;
        $this->harga_beli_per_pcs = $botol->harga_beli_per_pcs;
        $this->stok_pcs = $botol->stok_pcs;
        
        $this->openModal();
    }

    public function delete($id)
    {
        Botol::find($id)->delete();
        session()->flash('message', 'Data Botol Berhasil Dihapus.');
        $this->loadBotols();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Kelola Data Botol</h3>
                <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    + Tambah Botol
                </button>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            @if($isOpen)
                <div class="mb-6 bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4">{{ $id_botol ? 'Edit' : 'Tambah' }} Botol</h3>
                    <form wire:submit.prevent="store">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Nama Botol</label>
                                <input type="text" wire:model="nama_botol" placeholder="Contoh: Botol Kaca Spray" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_botol') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Kapasitas (ML)</label>
                                <input type="number" wire:model="kapasitas_ml" placeholder="Contoh: 30" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('kapasitas_ml') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Beli per Pcs (Rp)</label>
                                <input type="number" step="0.01" wire:model="harga_beli_per_pcs" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_beli_per_pcs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Jual per Pcs (Rp)</label>
                                <input type="number" step="0.01" wire:model="harga_jual_per_pcs" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_jual_per_pcs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Stok Tersedia (Pcs)</label>
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
                            <th class="px-4 py-3 text-left font-semibold">Nama Botol</th>
                            <th class="px-4 py-3 text-center font-semibold">Kapasitas</th>
                            <th class="px-4 py-3 text-left font-semibold">Harga Beli</th>
                            <th class="px-4 py-3 text-left font-semibold">Harga Jual</th>
                            <th class="px-4 py-3 text-center font-semibold">Stok (Pcs)</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($botols as $botol)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">{{ $botol->nama_botol }}</td>
                            <td class="px-4 py-3 text-center">{{ $botol->kapasitas_ml }} ML</td>
                            <td class="px-4 py-3">Rp {{ number_format($botol->harga_beli_per_pcs, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($botol->harga_jual_per_pcs, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">{{ number_format($botol->stok_pcs, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="edit({{ $botol->id_botol }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $botol->id_botol }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($botols) == 0)
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">Belum ada data botol.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>