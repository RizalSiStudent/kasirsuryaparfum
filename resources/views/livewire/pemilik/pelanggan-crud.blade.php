<?php

use Livewire\Volt\Component;
use App\Models\Pelanggan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

new #[Layout('layouts.app')] #[Title('Data Pelanggan - Surya Parfum')] class extends Component {
    public $pelanggans, $nama_pelanggan, $jenis_kelamin, $no_telepon, $alamat, $poin, $id_pelanggan;
    public $isOpen = false;

    // Properti untuk Pencarian dan Pengurutan
    public $search = '';
    public $sortColumn = 'nama_pelanggan'; 
    public $sortDirection = 'asc'; 

    public function sortBy($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortColumn = $column;
    }

    public function render(): mixed
    {
        $pelanggansQuery = Pelanggan::query();

        if ($this->search) {
            $pelanggansQuery->where('nama_pelanggan', 'like', '%' . $this->search . '%')
                            ->orWhere('no_telepon', 'like', '%' . $this->search . '%')
                            ->orWhere('alamat', 'like', '%' . $this->search . '%');
        }

        $pelanggansQuery->orderBy($this->sortColumn, $this->sortDirection);

        return view('livewire.pemilik.pelanggan-crud', [
            'pelanggansData' => $pelanggansQuery->get() 
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
        $this->id_pelanggan = '';
        $this->nama_pelanggan = '';
        $this->jenis_kelamin = '';
        $this->no_telepon = '';
        $this->alamat = '';
        $this->poin = 0; // Default poin untuk pelanggan baru
        
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate([
            'nama_pelanggan' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'no_telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
            'poin' => 'required|integer|min:0', // Validasi poin
        ], [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'poin.required' => 'Poin wajib diisi, minimal 0.',
            'poin.integer' => 'Poin harus berupa angka bulat.',
            'poin.min' => 'Poin tidak boleh bernilai negatif.',
        ]);

        Pelanggan::updateOrCreate(['id_pelanggan' => $this->id_pelanggan], [
            'nama_pelanggan' => $this->nama_pelanggan,
            'jenis_kelamin' => $this->jenis_kelamin,
            'no_telepon' => $this->no_telepon,
            'alamat' => $this->alamat,
            'poin' => $this->poin,
            // Jika data baru, tanggal_gabung otomatis terisi (jika diset di DB/Model)
        ]);

        session()->flash('message', $this->id_pelanggan ? 'Data Pelanggan Berhasil Diperbarui.' : 'Data Pelanggan Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
    }

    public function edit($id)
    {
        $this->resetErrorBag();
        
        $pelanggan = Pelanggan::findOrFail($id);
        $this->id_pelanggan = $id;
        $this->nama_pelanggan = $pelanggan->nama_pelanggan;
        $this->jenis_kelamin = $pelanggan->jenis_kelamin;
        $this->no_telepon = $pelanggan->no_telepon;
        $this->alamat = $pelanggan->alamat;
        $this->poin = $pelanggan->poin ?? 0;
        
        $this->openModal();
    }

    public function delete($id)
    {
        Pelanggan::find($id)->delete();
        session()->flash('message', 'Data Pelanggan Berhasil Dihapus.');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Kelola Data Pelanggan</h3>
                <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    + Tambah Pelanggan
                </button>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            @if($isOpen)
                <div class="mb-6 bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4">{{ $id_pelanggan ? 'Edit' : 'Tambah' }} Pelanggan</h3>
                    <form wire:submit.prevent="store">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nama_pelanggan" class="w-full border @error('nama_pelanggan') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('nama_pelanggan') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                                <select wire:model="jenis_kelamin" class="w-full border @error('jenis_kelamin') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                                @error('jenis_kelamin') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">No. Telepon</label>
                                <input type="text" wire:model="no_telepon" class="w-full border @error('no_telepon') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('no_telepon') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Saldo Poin <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-yellow-500">⭐</span>
                                    </div>
                                    <input type="number" wire:model="poin" min="0" class="w-full pl-10 border @error('poin') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2">
                                </div>
                                @error('poin') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Alamat</label>
                                <textarea wire:model="alamat" class="w-full border @error('alamat') border-red-500 @else dark:border-zinc-600 @enderror dark:bg-zinc-900 rounded-lg px-3 py-2" rows="2"></textarea>
                                @error('alamat') <span class="text-red-500 text-sm block mt-1 font-medium">{{ $message }}</span> @enderror
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
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, no telepon, atau alamat..." class="w-full md:w-1/3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
            </div>

            <div class="overflow-x-auto rounded-lg border dark:border-zinc-700 shadow-sm">
                <table class="min-w-full bg-white dark:bg-zinc-900">
                    <thead>
                        <tr class="bg-zinc-100 dark:bg-zinc-800 border-b dark:border-zinc-700">
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('nama_pelanggan')">
                                Nama
                                @if ($sortColumn === 'nama_pelanggan')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('jenis_kelamin')">
                                L/P
                                @if ($sortColumn === 'jenis_kelamin')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('no_telepon')">
                                No. Telepon
                                @if ($sortColumn === 'no_telepon')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold">Alamat</th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('tanggal_gabung')">
                                Tgl Gabung
                                @if ($sortColumn === 'tanggal_gabung')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors select-none" wire:click="sortBy('poin')">
                                Poin
                                @if ($sortColumn === 'poin')
                                    <span class="inline-block ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($pelanggansData as $pelanggan)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $pelanggan->nama_pelanggan }}</td>
                            <td class="px-4 py-3">
                                @if($pelanggan->jenis_kelamin == 'Laki-laki')
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">L</span>
                                @else
                                    <span class="bg-pink-100 text-pink-800 text-xs font-semibold px-2 py-0.5 rounded dark:bg-pink-900 dark:text-pink-300">P</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $pelanggan->no_telepon ?? '-' }}</td>
                            <td class="px-4 py-3" title="{{ $pelanggan->alamat }}">
                                {{ Str::limit($pelanggan->alamat ?? '-', 30) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $pelanggan->tanggal_gabung ? Carbon::parse($pelanggan->tanggal_gabung)->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 font-medium">
                                    <span class="text-yellow-500">⭐</span> {{ number_format($pelanggan->poin ?? 0) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <button wire:click="edit({{ $pelanggan->id_pelanggan }})" class="text-yellow-600 hover:text-yellow-800 px-2 font-medium">Edit</button>
                                <button wire:click="delete({{ $pelanggan->id_pelanggan }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</button>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if($pelanggansData->isEmpty())
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                {{ $search ? 'Tidak ada data pelanggan yang cocok dengan pencarian.' : 'Belum ada data pelanggan.' }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>