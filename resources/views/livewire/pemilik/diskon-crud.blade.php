<?php

use Livewire\Volt\Component;
use App\Models\Diskon;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Kelola Event Diskon - Surya Parfum')] class extends Component {
    public $search = '';
    
    // Properti Form
    public $id_diskon;
    public $nama_event;
    public $tanggal_mulai;
    public $tanggal_akhir;
    public $jenis_diskon = 'persentase';
    public $nilai_diskon;
    public $minimal_belanja = 0;
    public $khusus_pelanggan = false; 
    public $is_active = true;

    // State Modal
    public $isModalOpen = false;
    public $isEdit = false;

    public function rules()
    {
        return [
            'nama_event' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis_diskon' => 'required|in:persentase,nominal',
            'nilai_diskon' => 'required|numeric|min:1' . ($this->jenis_diskon === 'persentase' ? '|max:100' : ''),
            'minimal_belanja' => 'required|numeric|min:0',
            'khusus_pelanggan' => 'boolean', 
            'is_active' => 'boolean',
        ];
    }

    // --- TAMBAHAN BARU: Pesan Error Kustom Berbahasa Indonesia ---
    public function messages()
    {
        return [
            'nama_event.required' => '⚠️ Nama event promo wajib diisi.',
            'tanggal_mulai.required' => '⚠️ Pilih tanggal mulai promo.',
            'tanggal_akhir.required' => '⚠️ Pilih tanggal akhir promo.',
            'tanggal_akhir.after_or_equal' => '⚠️ Tanggal akhir tidak boleh mundur dari tanggal mulai.',
            'nilai_diskon.required' => '⚠️ Nilai potongan wajib diisi.',
            'nilai_diskon.min' => '⚠️ Nilai potongan minimal 1.',
            'nilai_diskon.max' => '⚠️ Karena jenis persentase, potongan maksimal 100%.',
            'minimal_belanja.required' => '⚠️ Minimal belanja wajib diisi (ketik 0 jika tidak ada syarat).',
            'minimal_belanja.min' => '⚠️ Minimal belanja tidak boleh bernilai minus.',
        ];
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->resetFields();
        $this->isModalOpen = true;
        $this->isEdit = false;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function resetFields()
    {
        $this->id_diskon = null;
        $this->nama_event = '';
        $this->tanggal_mulai = '';
        $this->tanggal_akhir = '';
        $this->jenis_diskon = 'persentase';
        $this->nilai_diskon = '';
        $this->minimal_belanja = 0;
        $this->khusus_pelanggan = false; 
        $this->is_active = true;
    }

    public function simpanDiskon()
    {
        $this->validate();

        Diskon::updateOrCreate(
            ['id_diskon' => $this->id_diskon],
            [
                'nama_event' => $this->nama_event,
                'tanggal_mulai' => $this->tanggal_mulai,
                'tanggal_akhir' => $this->tanggal_akhir,
                'jenis_diskon' => $this->jenis_diskon,
                'nilai_diskon' => $this->nilai_diskon,
                'minimal_belanja' => $this->minimal_belanja,
                'khusus_pelanggan' => $this->khusus_pelanggan, 
                'is_active' => $this->is_active,
            ]
        );

        $this->closeModal();
        session()->flash('message', $this->id_diskon ? 'Event diskon berhasil diperbarui!' : 'Event diskon baru berhasil dijadwalkan!');
        $this->resetFields();
    }

    public function edit($id)
    {
        $this->resetValidation();
        $diskon = Diskon::findOrFail($id);
        $this->id_diskon = $diskon->id_diskon;
        $this->nama_event = $diskon->nama_event;
        $this->tanggal_mulai = $diskon->tanggal_mulai;
        $this->tanggal_akhir = $diskon->tanggal_akhir;
        $this->jenis_diskon = $diskon->jenis_diskon;
        $this->nilai_diskon = $diskon->nilai_diskon;
        $this->minimal_belanja = $diskon->minimal_belanja;
        $this->khusus_pelanggan = (bool) $diskon->khusus_pelanggan; 
        $this->is_active = (bool) $diskon->is_active;

        $this->isEdit = true;
        $this->isModalOpen = true;
    }

    public function toggleStatus($id)
    {
        $diskon = Diskon::findOrFail($id);
        $diskon->update(['is_active' => !$diskon->is_active]);
    }

    public function hapus($id)
    {
        Diskon::destroy($id);
        session()->flash('message', 'Event diskon berhasil dihapus!');
    }

    public function with(): array
    {
        return [
            'diskons' => Diskon::where('nama_event', 'like', '%' . $this->search . '%')
                ->latest()
                ->get()
        ];
    }
}; ?>

<div class="p-6 flex flex-col gap-6 w-full h-full">
    
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold dark:text-gray-100">Manajemen Event Diskon</h2>
            <p class="text-sm text-gray-500">Jadwalkan potongan harga atau promo khusus member.</p>
        </div>
        <button wire:click="openModal" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all flex items-center gap-2 text-sm">
            ➕ Buat Event Promo
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col gap-4">
        <div class="w-full md:w-72">
            <input type="text" wire:model.live="search" placeholder="Cari nama event promo..." class="text-sm w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2 focus:ring-orange-500 focus:border-orange-500">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800 border-b dark:border-zinc-700 text-zinc-600 dark:text-gray-300 font-semibold text-left">
                        <th class="px-4 py-3">Nama Event Promo</th>
                        <th class="px-4 py-3">Masa Berlaku</th>
                        <th class="px-4 py-3">Target Promo</th>
                        <th class="px-4 py-3">Besar Potongan</th>
                        <th class="px-4 py-3">Min. Belanja</th>
                        <th class="px-4 py-3 text-center">Status Hari Ini</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-zinc-800">
                    @forelse($diskons as $row)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 text-zinc-700 dark:text-gray-300">
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $row->nama_event }}</td>
                            <td class="px-4 py-3 text-xs">
                                {{ \Carbon\Carbon::parse($row->tanggal_mulai)->format('d M Y') }} s/d <br>
                                {{ \Carbon\Carbon::parse($row->tanggal_akhir)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($row->khusus_pelanggan)
                                    <span class="px-2 py-0.5 text-xs rounded font-medium bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400">👤 Member</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded font-medium bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400">🌐 Umum</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold text-zinc-900 dark:text-white">
                                {{ $row->jenis_diskon === 'persentase' ? $row->nilai_diskon . ' %' : 'Rp ' . number_format($row->nilai_diskon, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3">Rp {{ number_format($row->minimal_belanja, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($row->is_valid)
                                    <span class="bg-green-100 text-green-700 dark:bg-green-950/60 dark:text-green-400 px-2 py-1 rounded-md text-xs font-bold border border-green-200 dark:border-green-900">Aktif</span>
                                @else
                                    <span class="bg-zinc-100 text-zinc-500 dark:bg-zinc-800 px-2 py-1 rounded-md text-xs font-medium border dark:border-zinc-700">Tdk Berlaku</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center flex justify-center gap-2">
                                <button wire:click="edit({{ $row->id_diskon }})" class="text-blue-600 hover:text-blue-700 text-xs font-semibold bg-blue-50 dark:bg-blue-950/30 px-2 py-1 rounded border border-blue-200 dark:border-blue-900">⚙️ Edit</button>
                                <button onclick="confirm('Hapus event promo ini?') || event.stopImmediatePropagation()" wire:click="hapus({{ $row->id_diskon }})" class="text-red-600 hover:text-red-700 text-xs font-semibold bg-red-50 dark:bg-red-950/30 px-2 py-1 rounded border border-red-200 dark:border-red-900">❌ Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Belum ada event diskon yang dijadwalkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm" wire:click="closeModal"></div>
            
            <div class="relative bg-white dark:bg-zinc-900 border dark:border-zinc-800 rounded-xl max-w-md w-full shadow-2xl p-6 z-10 flex flex-col gap-4">
                <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-2">
                    <h3 class="text-lg font-bold dark:text-white">{{ $isEdit ? 'Ubah Event Promo' : 'Buat Event Promo Baru' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                </div>

                <form wire:submit.prevent="simpanDiskon" class="flex flex-col gap-3 text-sm">
                    <div>
                        <label class="block font-medium dark:text-gray-300 mb-1">Nama Event Promo</label>
                        <input type="text" wire:model="nama_event" placeholder="Contoh: Promo Member Setia" class="w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2 focus:ring-orange-500 focus:border-orange-500">
                        @error('nama_event') <span class="text-red-500 text-xs mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium dark:text-gray-300 mb-1">Dari Tanggal</label>
                            <input type="date" wire:model="tanggal_mulai" class="w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2">
                            @error('tanggal_mulai') <span class="text-red-500 text-xs mt-0.5 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-medium dark:text-gray-300 mb-1">Sampai Tanggal</label>
                            <input type="date" wire:model="tanggal_akhir" class="w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2">
                            @error('tanggal_akhir') <span class="text-red-500 text-xs mt-0.5 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium dark:text-gray-300 mb-1">Jenis Potongan</label>
                            <select wire:model.live="jenis_diskon" class="w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2">
                                <option value="persentase">Persentase (%)</option>
                                <option value="nominal">Nominal (Rp)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium dark:text-gray-300 mb-1">Nilai Potongan</label>
                            <input type="number" wire:model="nilai_diskon" placeholder="{{ $jenis_diskon === 'persentase' ? 'Contoh: 10' : 'Contoh: 5000' }}" class="w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2 focus:ring-orange-500 focus:border-orange-500">
                            @error('nilai_diskon') <span class="text-red-500 text-xs mt-0.5 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium dark:text-gray-300 mb-1">Syarat Minimal Belanja (Rp)</label>
                        <input type="number" wire:model="minimal_belanja" placeholder="Isi 0 jika tanpa syarat belanja" class="w-full border dark:border-zinc-700 dark:bg-zinc-800 dark:text-white rounded-lg px-3 py-2 focus:ring-orange-500 focus:border-orange-500">
                        @error('minimal_belanja') <span class="text-red-500 text-xs mt-0.5 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2 mt-2 bg-orange-50 dark:bg-orange-950/20 p-2.5 rounded-lg border border-orange-200 dark:border-orange-900/50">
                        <input type="checkbox" wire:model="khusus_pelanggan" id="khusus_pelanggan" class="rounded text-orange-600 border-zinc-300 dark:border-zinc-700 focus:ring-orange-500 w-4 h-4 cursor-pointer">
                        <label for="khusus_pelanggan" class="font-bold text-orange-800 dark:text-orange-400 select-none cursor-pointer">Promo KHUSUS Pelanggan / Member</label>
                    </div>

                    <div class="flex items-center gap-2 mt-1">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="rounded text-green-600 border-zinc-300 dark:border-zinc-700 w-4 h-4 cursor-pointer">
                        <label for="is_active" class="font-medium dark:text-gray-300 select-none cursor-pointer">Aktifkan Event Promo Ini</label>
                    </div>

                    <div class="flex justify-end gap-2 border-t dark:border-zinc-800 pt-3 mt-2">
                        <button type="button" wire:click="closeModal" class="bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-white font-semibold py-2 px-4 rounded-lg">Batal</button>
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-5 rounded-lg shadow-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>