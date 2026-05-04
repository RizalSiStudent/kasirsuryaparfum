<?php

use Livewire\Volt\Component;
use App\Models\Parfum;
use App\Models\Supplier; // <-- Import model Supplier
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Parfum - Surya Parfum')] class extends Component {
    use WithFileUploads;

    public $parfums, $suppliers, $id_supplier, $nama_parfum, $harga_jual_per_ml, $harga_beli_per_ml, $stok_ml, $foto_parfum, $id_parfum;
    public $isOpen = false;

    public function mount()
    {
        $this->suppliers = Supplier::all(); // Load data supplier untuk dropdown
        $this->loadParfums();
    }

    public function loadParfums()
    {
        // Load parfum beserta data supplier-nya (Eager Loading)
        $this->parfums = Parfum::with('supplier')->get();
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
        $this->id_parfum = '';
        $this->id_supplier = ''; // <-- Reset id_supplier
        $this->nama_parfum = '';
        $this->harga_jual_per_ml = '';
        $this->harga_beli_per_ml = '';
        $this->stok_ml = '';
        $this->foto_parfum = '';
    }

    public function store()
    {
        $this->validate([
            'id_supplier' => 'required', // <-- Validasi wajib pilih supplier
            'nama_parfum' => 'required|string|max:100',
            'harga_jual_per_ml' => 'required|numeric',
            'harga_beli_per_ml' => 'required|numeric',
            'stok_ml' => 'required|numeric',
        ], [
            'id_supplier.required' => 'Supplier wajib dipilih!',
        ]);

        Parfum::updateOrCreate(['id_parfum' => $this->id_parfum], [
            'id_supplier' => $this->id_supplier, // <-- Simpan id_supplier
            'nama_parfum' => $this->nama_parfum,
            'harga_jual_per_ml' => $this->harga_jual_per_ml,
            'harga_beli_per_ml' => $this->harga_beli_per_ml,
            'stok_ml' => $this->stok_ml,
        ]);

        session()->flash('message', $this->id_parfum ? 'Data Parfum Berhasil Diperbarui.' : 'Data Parfum Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
        $this->loadParfums();
    }

    public function edit($id)
    {
        $parfum = Parfum::findOrFail($id);
        $this->id_parfum = $id;
        $this->id_supplier = $parfum->id_supplier; // <-- Isi id_supplier ke form
        $this->nama_parfum = $parfum->nama_parfum;
        $this->harga_jual_per_ml = $parfum->harga_jual_per_ml;
        $this->harga_beli_per_ml = $parfum->harga_beli_per_ml;
        $this->stok_ml = $parfum->stok_ml;
        
        $this->openModal();
    }

    public function delete($id)
    {
        Parfum::find($id)->delete();
        session()->flash('message', 'Data Parfum Berhasil Dihapus.');
        $this->loadParfums();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Kelola Data Parfum</h3>
                <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    + Tambah Parfum
                </button>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            @if($isOpen)
                <div class="mb-6 bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4">{{ $id_parfum ? 'Edit' : 'Tambah' }} Parfum</h3>
                    <form wire:submit.prevent="store">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Pilih Supplier</label>
                                <select wire:model="id_supplier" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id_supplier }}">{{ $supplier->nama_supplier }}</option>
                                    @endforeach
                                </select>
                                @error('id_supplier') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Nama Parfum</label>
                                <input type="text" wire:model="nama_parfum" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_parfum') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Stok Awal (ML)</label>
                                <input type="number" step="0.01" wire:model="stok_ml" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('stok_ml') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Beli per ML (Rp)</label>
                                <input type="number" step="0.01" wire:model="harga_beli_per_ml" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_beli_per_ml') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Jual per ML (Rp)</label>
                                <input type="number" step="0.01" wire:model="harga_jual_per_ml" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_jual_per_ml') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                            <th class="px-4 py-3 text-left font-semibold">Nama Parfum</th>
                            <th class="px-4 py-3 text-left font-semibold">Supplier</th>
                            <th class="px-4 py-3 text-left font-semibold">Harga Beli/ML</th>
                            <th class="px-4 py-3 text-left font-semibold">Harga Jual/ML</th>
                            <th class="px-4 py-3 text-left font-semibold">Stok (ML)</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($parfums as $parfum)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">{{ $parfum->nama_parfum }}</td>
                            <td class="px-4 py-3">
                                @if($parfum->supplier)
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                        {{ $parfum->supplier->nama_supplier }}
                                    </span>
                                @else
                                    <span class="text-gray-500 italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format($parfum->harga_beli_per_ml, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">Rp {{ number_format($parfum->harga_jual_per_ml, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $parfum->stok_ml }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="edit({{ $parfum->id_parfum }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $parfum->id_parfum }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($parfums) == 0)
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">Belum ada data parfum.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>