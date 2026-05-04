<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Supplier - Surya Parfum')] class extends Component {
    public $suppliers, $nama_supplier, $no_telepon, $alamat, $id_supplier;
    public $isOpen = false;

    public function mount()
    {
        $this->loadSuppliers();
    }

    public function loadSuppliers()
    {
        $this->suppliers = Supplier::all();
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
        $this->id_supplier = '';
        $this->nama_supplier = '';
        $this->no_telepon = '';
        $this->alamat = '';
    }

    public function store()
    {
        $this->validate([
            'nama_supplier' => 'required|string|max:100',
            'no_telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
        ]);

        Supplier::updateOrCreate(['id_supplier' => $this->id_supplier], [
            'nama_supplier' => $this->nama_supplier,
            'no_telepon' => $this->no_telepon,
            'alamat' => $this->alamat,
        ]);

        session()->flash('message', $this->id_supplier ? 'Data Supplier Berhasil Diperbarui.' : 'Data Supplier Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
        $this->loadSuppliers();
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->id_supplier = $id;
        $this->nama_supplier = $supplier->nama_supplier;
        $this->no_telepon = $supplier->no_telepon;
        $this->alamat = $supplier->alamat;
        
        $this->openModal();
    }

    public function delete($id)
    {
        Supplier::find($id)->delete();
        session()->flash('message', 'Data Supplier Berhasil Dihapus.');
        $this->loadSuppliers();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Kelola Data Supplier</h3>
                <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    + Tambah Supplier
                </button>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            @if($isOpen)
                <div class="mb-6 bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4">{{ $id_supplier ? 'Edit' : 'Tambah' }} Supplier</h3>
                    <form wire:submit.prevent="store">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Nama Supplier</label>
                                <input type="text" wire:model="nama_supplier" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_supplier') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">No. Telepon</label>
                                <input type="text" wire:model="no_telepon" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('no_telepon') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Alamat</label>
                                <textarea wire:model="alamat" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2" rows="3"></textarea>
                                @error('alamat') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                            <th class="px-4 py-3 text-left font-semibold">Nama Supplier</th>
                            <th class="px-4 py-3 text-left font-semibold">No. Telepon</th>
                            <th class="px-4 py-3 text-left font-semibold">Alamat</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($suppliers as $supplier)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">{{ $supplier->nama_supplier }}</td>
                            <td class="px-4 py-3">{{ $supplier->no_telepon ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $supplier->alamat ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="edit({{ $supplier->id_supplier }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $supplier->id_supplier }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($suppliers) == 0)
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-zinc-500">Belum ada data supplier.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>