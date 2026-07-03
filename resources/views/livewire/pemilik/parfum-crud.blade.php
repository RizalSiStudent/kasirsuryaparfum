<?php

use Livewire\Volt\Component;
use App\Models\Parfum;
use App\Models\Supplier;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Data Parfum - Surya Parfum')] class extends Component {
    use WithFileUploads;

    public $suppliers, $id_supplier, $nama_parfum, $grade, $gender, $deskripsi, $harga_jual_per_ml, $harga_beli_per_ml, $stok_ml, $foto_parfum, $id_parfum;
    public $isOpen = false;

    // Properti untuk Pencarian dan Pengurutan
    public $search = '';
    public $sortColumn = 'nama_parfum'; // Default urut berdasarkan nama
    public $sortDirection = 'asc'; // Default urutan naik (A-Z)

    public function mount()
    {
        $this->suppliers = Supplier::all(); 
    }

    // Fungsi untuk mengubah arah pengurutan
    public function sortBy($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortColumn = $column;
    }

    // Menggunakan fungsi render() agar reaktif, ditambahkan return type : mixed
    public function render(): mixed
    {
        $parfumsQuery = Parfum::with('supplier');

        // Logika Pencarian
        if ($this->search) {
            $parfumsQuery->where('nama_parfum', 'like', '%' . $this->search . '%')
                         ->orWhere('deskripsi', 'like', '%' . $this->search . '%')
                         ->orWhereHas('supplier', function($query) {
                             $query->where('nama_supplier', 'like', '%' . $this->search . '%');
                         }); // Pencarian juga mencakup nama supplier dan deskripsi
        }

        // Logika Pengurutan
        $parfumsQuery->orderBy($this->sortColumn, $this->sortDirection);

        return view('livewire.pemilik.parfum-crud', [
            'parfums' => $parfumsQuery->get() 
        ]);
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
        $this->id_supplier = ''; 
        $this->nama_parfum = '';
        $this->grade = '';
        $this->gender = '';
        $this->deskripsi = '';
        $this->harga_jual_per_ml = '';
        $this->harga_beli_per_ml = '';
        $this->stok_ml = '';
        $this->foto_parfum = '';
        
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate([
            'id_supplier' => 'required',
            'nama_parfum' => 'required|string|max:100|unique:parfums,nama_parfum,' . $this->id_parfum . ',id_parfum',
            'grade' => 'required|in:Premium,Standar',
            'gender' => 'required|in:Male,Female,Unisex',
            'deskripsi' => 'nullable|string|max:255',
            'harga_beli_per_ml' => 'required|numeric|min:0',
            'harga_jual_per_ml' => 'required|numeric|gt:harga_beli_per_ml',
            'stok_ml' => 'required|numeric|min:0',
        ], [
            'id_supplier.required' => 'Supplier wajib dipilih!',
            'nama_parfum.required' => 'Nama parfum tidak boleh kosong!',
            'nama_parfum.max' => 'Nama parfum terlalu panjang (maks 100 karakter).',
            'nama_parfum.unique' => 'Peringatan: Nama parfum ini sudah terdaftar di database!',
            'grade.required' => 'Grade wajib dipilih!',
            'grade.in' => 'Pilihan grade tidak valid!',
            'gender.required' => 'Gender wajib dipilih!',
            'gender.in' => 'Pilihan gender tidak valid!',
            'harga_beli_per_ml.required' => 'Harga beli wajib diisi!',
            'harga_jual_per_ml.required' => 'Harga jual wajib diisi!',
            'harga_jual_per_ml.gt' => 'Peringatan: Harga jual harus lebih tinggi dari harga beli!',
            'stok_ml.required' => 'Stok wajib diisi!',
        ]);

        Parfum::updateOrCreate(['id_parfum' => $this->id_parfum], [
            'id_supplier' => $this->id_supplier, 
            'nama_parfum' => $this->nama_parfum,
            'grade' => $this->grade,
            'gender' => $this->gender,
            'deskripsi' => $this->deskripsi,
            'harga_jual_per_ml' => $this->harga_jual_per_ml,
            'harga_beli_per_ml' => $this->harga_beli_per_ml,
            'stok_ml' => $this->stok_ml,
        ]);

        session()->flash('message', $this->id_parfum ? 'Data Parfum Berhasil Diperbarui.' : 'Data Parfum Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
    }

    public function edit($id)
    {
        $this->resetErrorBag(); 
        
        $parfum = Parfum::findOrFail($id);
        $this->id_parfum = $id;
        $this->id_supplier = $parfum->id_supplier; 
        $this->nama_parfum = $parfum->nama_parfum;
        $this->grade = $parfum->grade;
        $this->gender = $parfum->gender;
        $this->deskripsi = $parfum->deskripsi;
        $this->harga_jual_per_ml = $parfum->harga_jual_per_ml;
        $this->harga_beli_per_ml = $parfum->harga_beli_per_ml;
        $this->stok_ml = $parfum->stok_ml;
        
        $this->openModal();
    }

    public function delete($id)
    {
        Parfum::find($id)->delete();
        session()->flash('message', 'Data Parfum Berhasil Dihapus.');
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
                                <label class="block text-sm font-medium mb-1">Pilih Supplier <span class="text-red-500">*</span></label>
                                <select wire:model="id_supplier" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id_supplier }}">{{ $supplier->nama_supplier }}</option>
                                    @endforeach
                                </select>
                                @error('id_supplier') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2 lg:col-span-1">
                                <label class="block text-sm font-medium mb-1">Nama Parfum <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nama_parfum" class="w-full border @error('nama_parfum') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_parfum') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Grade <span class="text-red-500">*</span></label>
                                <select wire:model="grade" class="w-full border @error('grade') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                    <option value="">-- Pilih Grade --</option>
                                    <option value="Premium">Premium</option>
                                    <option value="Standar">Standar</option>
                                </select>
                                @error('grade') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Gender <span class="text-red-500">*</span></label>
                                <select wire:model="gender" class="w-full border @error('gender') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                    <option value="">-- Pilih Gender --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Unisex">Unisex</option>
                                </select>
                                @error('gender') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Stok Awal (ML) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" wire:model="stok_ml" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('stok_ml') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Beli per ML (Rp) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" wire:model="harga_beli_per_ml" class="w-full border @error('harga_beli_per_ml') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_beli_per_ml') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Harga Jual per ML (Rp) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" wire:model="harga_jual_per_ml" class="w-full border @error('harga_jual_per_ml') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('harga_jual_per_ml') 
                                    <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> 
                                @enderror
                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                    <span class="font-semibold">💡 Rekomendasi:</span> Harga jual harus lebih tinggi dari harga beli untuk mendapatkan margin keuntungan.
                                </span>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Deskripsi Singkat</label>
                                <textarea wire:model="deskripsi" rows="3" class="w-full border @error('deskripsi') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2 placeholder-gray-400" placeholder="Contoh: Aroma segar citrus cocok untuk siang hari..."></textarea>
                                @error('deskripsi') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
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
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama parfum atau supplier..." class="w-full md:w-1/3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
            </div>

            <div class="overflow-x-auto rounded-lg border dark:border-zinc-700 shadow-sm">
                <table class="min-w-full bg-white dark:bg-zinc-900">
                    <thead>
                        <tr class="bg-zinc-100 dark:bg-zinc-800 border-b dark:border-zinc-700">
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('nama_parfum')">
                                Nama Parfum & Deskripsi
                                @if ($sortColumn === 'nama_parfum')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('grade')">
                                Grade
                                @if ($sortColumn === 'grade')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('gender')">
                                Gender
                                @if ($sortColumn === 'gender')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold">
                                Supplier
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('harga_jual_per_ml')">
                                Harga Jual/ML
                                @if ($sortColumn === 'harga_jual_per_ml')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('stok_ml')">
                                Stok (ML)
                                @if ($sortColumn === 'stok_ml')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($parfums as $parfum)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $parfum->nama_parfum }}</div>
                                @if($parfum->deskripsi)
                                    <div class="text-sm text-gray-500 dark:text-gray-400 max-w-[250px] truncate" title="{{ $parfum->deskripsi }}">
                                        {{ $parfum->deskripsi }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($parfum->grade === 'Premium')
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Premium</span>
                                @elseif($parfum->grade === 'Standar')
                                    <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">Standar</span>
                                @else
                                    <span class="text-gray-500 italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($parfum->gender === 'Male')
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">Male</span>
                                @elseif($parfum->gender === 'Female')
                                    <span class="bg-pink-100 text-pink-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-pink-900 dark:text-pink-300">Female</span>
                                @elseif($parfum->gender === 'Unisex')
                                    <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">Unisex</span>
                                @else
                                    <span class="text-gray-500 italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($parfum->supplier)
                                    {{ $parfum->supplier->nama_supplier }}
                                @else
                                    <span class="text-gray-500 italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format($parfum->harga_jual_per_ml, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ number_format((int) $parfum->stok_ml, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <button wire:click="edit({{ $parfum->id_parfum }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $parfum->id_parfum }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if($parfums->isEmpty())
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                {{ $search ? 'Tidak ada data yang cocok dengan pencarian Anda.' : 'Belum ada data parfum.' }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>