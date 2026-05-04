<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Kelola Karyawan - Surya Parfum')] class extends Component {
    public $karyawans, $name, $email, $password, $peran, $id_karyawan;
    public $isOpen = false;

    public function mount()
    {
        $this->loadKaryawans();
    }

    public function loadKaryawans()
    {
        // Ambil semua data pengguna
        $this->karyawans = User::all();
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
        $this->id_karyawan = '';
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->peran = '';
    }

    public function store()
    {
        // Validasi Dinamis: Password wajib diisi saat tambah baru, tapi opsional saat edit
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->id_karyawan,
            'peran' => 'required|in:pemilik,kasir,admin_stok',
        ];

        if (!$this->id_karyawan) {
            $rules['password'] = 'required|min:8'; // Wajib kalau bikin akun baru
        } else {
            $rules['password'] = 'nullable|min:8'; // Boleh kosong kalau cuma edit nama/role
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'peran' => $this->peran,
        ];

        // Jika form password diisi, enkripsi passwordnya sebelum masuk database
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->id_karyawan], $data);

        session()->flash('message', $this->id_karyawan ? 'Data Karyawan Berhasil Diperbarui.' : 'Data Karyawan Berhasil Ditambahkan.');

        $this->closeModal();
        $this->resetFields();
        $this->loadKaryawans();
    }

    public function edit($id)
    {
        $karyawan = User::findOrFail($id);
        $this->id_karyawan = $id;
        $this->name = $karyawan->name;
        $this->email = $karyawan->email;
        $this->peran = $karyawan->peran;
        $this->password = ''; // Sengaja dikosongkan agar password lama tidak tertimpa kecuali diisi
        
        $this->openModal();
    }

    public function delete($id)
    {
        // Mencegah owner menghapus akunnya sendiri yang sedang dipakai login
        if (auth()->user()->id == $id) {
            session()->flash('error', 'Akses Ditolak: Anda tidak dapat menghapus akun Anda sendiri!');
            return;
        }

        User::find($id)->delete();
        session()->flash('message', 'Data Karyawan Berhasil Dihapus.');
        $this->loadKaryawans();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Kelola Akses Karyawan</h3>
                <button wire:click="create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all">
                    + Tambah Karyawan
                </button>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if($isOpen)
                <div class="mb-6 bg-zinc-50 dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4">{{ $id_karyawan ? 'Edit' : 'Tambah' }} Akun Karyawan</h3>
                    <form wire:submit.prevent="store">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                                <input type="text" wire:model="name" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Email Karyawan</label>
                                <input type="email" wire:model="email" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Peran Akses (Role)</label>
                                <select wire:model="peran" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                    <option value="">-- Pilih Peran --</option>
                                    <option value="kasir">Kasir</option>
                                    <option value="admin_stok">Admin Stok</option>
                                    <option value="pemilik">Pemilik (Owner)</option>
                                </select>
                                @error('peran') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Password
                                    @if($id_karyawan)
                                        <span class="text-xs text-blue-500 font-normal">(Kosongkan jika tidak ingin mengubah password)</span>
                                    @endif
                                </label>
                                <input type="password" wire:model="password" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 rounded-lg px-3 py-2" placeholder="Minimal 8 karakter">
                                @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-sm transition-all">Simpan Akun</button>
                            <button type="button" wire:click="closeModal" class="bg-zinc-500 hover:bg-zinc-600 text-white px-4 py-2 rounded-lg shadow-sm transition-all">Batal</button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border dark:border-zinc-700 shadow-sm">
                <table class="min-w-full bg-white dark:bg-zinc-900">
                    <thead>
                        <tr class="bg-zinc-100 dark:bg-zinc-800 border-b dark:border-zinc-700">
                            <th class="px-4 py-3 text-left font-semibold">Nama Lengkap</th>
                            <th class="px-4 py-3 text-left font-semibold">Email</th>
                            <th class="px-4 py-3 text-center font-semibold">Role/Peran</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-zinc-700">
                        @foreach($karyawans as $k)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $k->name }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $k->email }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($k->peran == 'pemilik')
                                    <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">Pemilik</span>
                                @elseif($k->peran == 'admin_stok')
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Admin Stok</span>
                                @else
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Kasir</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="edit({{ $k->id }})" class="text-blue-600 hover:text-blue-800 px-2 font-medium">Edit</button>
                                
                                @if(auth()->user()->id != $k->id)
                                    <button wire:click="delete({{ $k->id }})" class="text-red-600 hover:text-red-800 px-2 font-medium" onclick="return confirm('Yakin ingin menghapus akses akun ini?')">Hapus</button>
                                @else
                                    <span class="text-gray-400 px-2 text-sm italic">(Anda)</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>