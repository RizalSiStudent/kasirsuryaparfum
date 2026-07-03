<?php

use Livewire\Volt\Component;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Supplier - Surya Parfum')] class extends Component {
    public $suppliers, $nama_perusahaan, $nama_supplier, $no_telepon, $alamat, $id_supplier;
    public $isOpen = false;
    
    // Properti pencarian
    public $search = '';

    public function mount()
    {
        $this->loadSuppliers();
    }

    public function loadSuppliers()
    {
        if ($this->search) {
            $this->suppliers = Supplier::where('nama_perusahaan', 'like', '%' . $this->search . '%')
                                       ->orWhere('nama_supplier', 'like', '%' . $this->search . '%')
                                       ->orderBy('nama_perusahaan', 'asc')
                                       ->get();
        } else {
            $this->suppliers = Supplier::orderBy('nama_perusahaan', 'asc')->get();
        }
    }

    // Fungsi yang otomatis dipanggil saat kolom pencarian diketik
    public function updatedSearch()
    {
        $this->loadSuppliers();
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
        $this->nama_perusahaan = '';
        $this->nama_supplier = '';
        $this->no_telepon = '';
        $this->alamat = '';
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate([
            'nama_perusahaan' => 'required|string|max:100',
            'nama_supplier' => 'required|string|max:100',
            'no_telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
        ], [
            'nama_perusahaan.required' => 'Nama Perusahaan wajib diisi.',
            'nama_supplier.required' => 'Nama kontak / penanggung jawab wajib diisi.',
        ]);

        Supplier::updateOrCreate(['id_supplier' => $this->id_supplier], [
            'nama_perusahaan' => $this->nama_perusahaan,
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
        $this->resetErrorBag();
        $supplier = Supplier::findOrFail($id);
        $this->id_supplier = $id;
        $this->nama_perusahaan = $supplier->nama_perusahaan;
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
                                <label class="block text-sm font-medium mb-1">Nama Perusahaan <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nama_perusahaan" placeholder="PT / CV / Toko..." class="w-full border @error('nama_perusahaan') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_perusahaan') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Nama Merk <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nama_supplier" placeholder="Nama penanggung jawab..." class="w-full border @error('nama_supplier') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_supplier') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2 lg:col-span-1">
                                <label class="block text-sm font-medium mb-1">No. Telepon</label>
                                <input type="text" wire:model="no_telepon" class="w-full border @error('no_telepon') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('no_telepon') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Alamat</label>
                                <textarea wire:model="alamat" class="w-full border @error('alamat') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2" rows="3"></textarea>
                                @error('alamat') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-sm transition-all">Simpan</button>
                            <button type="button" wire:click="closeModal" class="bg-zinc-500 hover:bg-zinc-600 text-white px-4 py-2 rounded-lg shadow-sm transition-all">Batal</button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="mb-4">
                <div class="relative w-full md:w-1/3">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama perusahaan atau nama merk..." class="w-full pl-9 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border dark:border-zinc-700 shadow-sm">
                <table class="min-w-full bg-white dark:bg-zinc-900">
                    <thead>
                        <tr class="bg-zinc-100 dark:bg-zinc-800 border-b dark:border-zinc-700">
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Nama Perusahaan</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Nama Merk</th>
                            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">No. Telepon</th>
                            <th class="px-4 py-3 text-left font-semibold">Alamat</th>
                            <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($suppliers as $supplier)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $supplier->nama_perusahaan }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $supplier->nama_supplier }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $supplier->no_telepon ?? '-' }}</td>
                            <td class="px-4 py-3">{{ Str::limit($supplier->alamat ?? '-', 40) }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <button wire:click="edit({{ $supplier->id_supplier }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $supplier->id_supplier }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($suppliers) == 0)
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                                {{ $search ? 'Tidak ada data supplier yang cocok dengan pencarian Anda.' : 'Belum ada data supplier.' }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>