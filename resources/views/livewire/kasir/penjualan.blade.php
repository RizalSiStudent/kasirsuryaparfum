<?php

use Livewire\Volt\Component;
use App\Models\Parfum;
use App\Models\Botol;
use App\Models\ParfumJadi;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Diskon; 
use Carbon\Carbon; 
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Midtrans\Snap;
use Midtrans\Config;

new #[Layout('layouts.app')] #[Title('Kasir - Surya Parfum')] class extends Component {
    public $parfums, $botols, $parfum_jadis, $pelanggans;
    
    // Form Pembayaran
    public $id_pelanggan = '';
    public $metode_pembayaran = 'Tunai';
    public $uang_dibayar = '';
    public $kembalian = 0;
    
    // State Form "Racikan Parfum"
    public $id_botol_pilihan = '';
    public $kapasitas_botol_pilihan = 0;
    public $id_parfum_pilihan = '';
    public $jumlah_ml_pilihan = '';
    public $racikan_sementara = []; 
    public $total_ml_racikan = 0;

    // State Form "Parfum Jadi"
    public $id_parfum_jadi_pilihan = '';
    public $qty_parfum_jadi = 1;

    // Keranjang Belanja Utama
    public $keranjang = [];
    public $subtotal_belanja = 0; 
    public $potongan_diskon = 0;  
    public $total_belanja = 0;    
    
    public $diskon_aktif = null;  
    public $ada_promo_member = false; // <-- State baru untuk deteksi UI peringatan promo
    public $nota_terakhir = null; 
    public $pending_snap_token = null; 
    
    public function mount()
    {
        $this->loadDataMaster();
        $this->hitungTotalBelanja(); // Hitung keranjang dan promo saat awal halaman dibuka
    }

    public function loadDataMaster()
    {
        $this->parfums = Parfum::where('stok_ml', '>', 0)->get();
        $this->botols = Botol::where('stok_pcs', '>', 0)->get();
        $this->parfum_jadis = ParfumJadi::where('stok_pcs', '>', 0)->get();
        $this->pelanggans = Pelanggan::all();
    }

    public function updatedMetodePembayaran($value)
    {
        if ($value !== 'Tunai') {
            $this->uang_dibayar = '';
            $this->kembalian = 0;
        }
    }

    public function updatedUangDibayar($value)
    {
        $this->hitungKembalian();
    }

    public function hitungKembalian()
    {
        $bayar = (int) $this->uang_dibayar;
        if ($bayar >= $this->total_belanja) {
            $this->kembalian = $bayar - $this->total_belanja;
        } else {
            $this->kembalian = 0; 
        }
    }

    public function updatedIdPelanggan($value)
    {
        // Fungsi ini memicu perhitungan ulang saat pelanggan dipilih/dihapus
        $this->hitungTotalBelanja(); 
    }

    // --- LOGIKA PARFUM RACIKAN ---
    public function pilihBotol($value)
    {
        $this->id_botol_pilihan = $value; 
        if ($value === 'bawa_sendiri') {
            $this->kapasitas_botol_pilihan = 9999; 
        } elseif ($value) {
            $botol = Botol::find($value);
            $this->kapasitas_botol_pilihan = $botol->kapasitas_ml;
        } else {
            $this->kapasitas_botol_pilihan = 0;
        }
        $this->racikan_sementara = [];
        $this->hitungTotalRacikan();
    }

    public function tambahParfumKeRacikan()
    {
        $this->validate([
            'id_botol_pilihan' => 'required',
            'id_parfum_pilihan' => 'required',
            'jumlah_ml_pilihan' => 'required|numeric|min:1',
        ], [
            'id_botol_pilihan.required' => 'Pilih botol atau opsi bawa sendiri!',
            'id_parfum_pilihan.required' => 'Pilih parfum yang ingin ditambahkan!',
            'jumlah_ml_pilihan.required' => 'Masukkan takaran (ML)!',
        ]);

        $parfum = Parfum::find($this->id_parfum_pilihan);

        if ($parfum->stok_ml < $this->jumlah_ml_pilihan) {
            session()->flash('error_racikan', 'Stok ' . $parfum->nama_parfum . ' tidak cukup!');
            return;
        }

        if ($this->id_botol_pilihan !== 'bawa_sendiri' && ($this->total_ml_racikan + $this->jumlah_ml_pilihan) > $this->kapasitas_botol_pilihan) {
            session()->flash('error_racikan', 'Takaran melebihi kapasitas botol!');
            return;
        }

        $this->racikan_sementara[] = [
            'id_parfum' => $parfum->id_parfum,
            'nama_parfum' => $parfum->nama_parfum,
            'ml' => $this->jumlah_ml_pilihan,
            'subtotal' => $parfum->harga_jual_per_ml * $this->jumlah_ml_pilihan,
            'harga_beli_per_ml' => $parfum->harga_beli_per_ml
        ];

        $this->hitungTotalRacikan();
        
        $this->id_parfum_pilihan = '';
        $this->jumlah_ml_pilihan = '';
    }

    public function hapusDariRacikan($index)
    {
        unset($this->racikan_sementara[$index]);
        $this->racikan_sementara = array_values($this->racikan_sementara);
        $this->hitungTotalRacikan();
    }

    public function hitungTotalRacikan()
    {
        $this->total_ml_racikan = array_sum(array_column($this->racikan_sementara, 'ml'));
    }

    public function masukkanKeKeranjang()
    {
        if (empty($this->id_botol_pilihan) || count($this->racikan_sementara) === 0) {
            session()->flash('error_racikan', 'Botol belum dipilih atau belum ada parfum yang diracik!');
            return;
        }

        if ($this->id_botol_pilihan === 'bawa_sendiri') {
            $botol_data = [
                'id' => null,
                'nama' => 'Bawa Botol Sendiri (Refill)',
                'kapasitas' => 'Sesuai Takaran',
                'harga' => 0,
                'harga_beli' => 0
            ];
        } else {
            $botol = Botol::find($this->id_botol_pilihan);
            $botol_data = [
                'id' => $botol->id_botol,
                'nama' => $botol->nama_botol,
                'kapasitas' => $botol->kapasitas_ml . ' ML',
                'harga' => $botol->harga_jual_per_pcs,
                'harga_beli' => $botol->harga_beli_per_pcs 
            ];
        }
        
        $subtotal_cairan = array_sum(array_column($this->racikan_sementara, 'subtotal'));
        $subtotal_keseluruhan = $botol_data['harga'] + $subtotal_cairan;

        $this->keranjang[] = [
            'tipe' => 'racikan',
            'botol' => $botol_data,
            'parfums' => $this->racikan_sementara,
            'total_ml' => $this->total_ml_racikan,
            'subtotal' => $subtotal_keseluruhan
        ];

        $this->hitungTotalBelanja();

        $this->id_botol_pilihan = '';
        $this->kapasitas_botol_pilihan = 0;
        $this->racikan_sementara = [];
        $this->total_ml_racikan = 0;
    }

    // --- LOGIKA PARFUM JADI ---
    public function tambahParfumJadiKeKeranjang()
    {
        $this->validate([
            'id_parfum_jadi_pilihan' => 'required',
            'qty_parfum_jadi' => 'required|numeric|min:1',
        ]);

        $pj = ParfumJadi::find($this->id_parfum_jadi_pilihan);

        if ($pj->stok_pcs < $this->qty_parfum_jadi) {
            session()->flash('error_jadi', 'Stok ' . $pj->nama_parfum . ' tidak mencukupi!');
            return;
        }

        $this->keranjang[] = [
            'tipe' => 'jadi',
            'id_parfum_jadi' => $pj->id_parfum_jadi,
            'nama_produk' => $pj->nama_parfum,
            'qty' => $this->qty_parfum_jadi,
            'harga_satuan' => $pj->harga_jual_per_pcs,
            'harga_beli_satuan' => $pj->harga_beli_per_pcs, 
            'subtotal' => $pj->harga_jual_per_pcs * $this->qty_parfum_jadi
        ];

        $this->hitungTotalBelanja();

        $this->id_parfum_jadi_pilihan = '';
        $this->qty_parfum_jadi = 1;
    }

    public function hapusDariKeranjang($index)
    {
        unset($this->keranjang[$index]);
        $this->keranjang = array_values($this->keranjang);
        $this->hitungTotalBelanja();
    }

    // --- LOGIKA PERHITUNGAN TOTAL & DISKON (DIPERBARUI) ---
    public function hitungTotalBelanja()
    {
        $this->subtotal_belanja = array_sum(array_column($this->keranjang, 'subtotal'));
        $this->potongan_diskon = 0;

        $hariIni = Carbon::now()->format('Y-m-d');
        
        // 1. Ambil semua diskon yang valid hari ini
        $semua_diskon = Diskon::where('is_active', true)
            ->where('tanggal_mulai', '<=', $hariIni)
            ->where('tanggal_akhir', '>=', $hariIni)
            ->get();

        // 2. Cek apakah ada satupun promo member yang aktif hari ini
        $this->ada_promo_member = $semua_diskon->filter(fn($d) => $d->khusus_pelanggan)->isNotEmpty();

        // 3. Logika Prioritas Promo
        if (!empty($this->id_pelanggan)) {
            // Jika kasir memilih nama pelanggan: Cari promo member dulu, kalau nggak ada, baru pakai promo umum
            $this->diskon_aktif = $semua_diskon->filter(fn($d) => $d->khusus_pelanggan)->first() 
                               ?? $semua_diskon->filter(fn($d) => !$d->khusus_pelanggan)->first();
        } else {
            // Jika pelanggan umum (tidak dipilih): Hanya boleh pakai promo umum
            $this->diskon_aktif = $semua_diskon->filter(fn($d) => !$d->khusus_pelanggan)->first();
        }

        // 4. Hitung potongannya jika syarat terpenuhi
        if ($this->diskon_aktif && $this->subtotal_belanja >= $this->diskon_aktif->minimal_belanja) {
            if ($this->diskon_aktif->jenis_diskon === 'persentase') {
                $this->potongan_diskon = ($this->subtotal_belanja * $this->diskon_aktif->nilai_diskon) / 100;
            } else {
                $this->potongan_diskon = $this->diskon_aktif->nilai_diskon;
            }
        }

        $this->total_belanja = $this->subtotal_belanja - $this->potongan_diskon;

        if ($this->total_belanja < 0) {
            $this->total_belanja = 0;
            $this->potongan_diskon = $this->subtotal_belanja;
        }

        $this->hitungKembalian(); 
    }

    public function simpanTransaksi()
    {
        if (count($this->keranjang) === 0) {
            session()->flash('error', 'Keranjang masih kosong!');
            return;
        }
        
        if ($this->metode_pembayaran === 'Tunai') {
            if ($this->uang_dibayar === '' || (int) $this->uang_dibayar < $this->total_belanja) {
                session()->flash('error', 'Transaksi gagal: Nominal uang tunai yang diberikan kurang!');
                return;
            }
        }

        DB::beginTransaction();

        try {
            $status_awal = ($this->metode_pembayaran === 'Tunai') ? 'success' : 'pending';

            $penjualan = Penjualan::create([
                'no_faktur' => 'INV-' . date('YmdHis'),
                'id_pengguna' => auth()->user()->id,
                'id_pelanggan' => $this->id_pelanggan ?: null,
                'subtotal' => $this->subtotal_belanja,
                'potongan_diskon' => $this->potongan_diskon,
                'uang_dibayar' => $this->metode_pembayaran === 'Tunai' ? (int) $this->uang_dibayar : null,
                'kembalian' => $this->metode_pembayaran === 'Tunai' ? $this->kembalian : null,
                'total_bayar' => $this->total_belanja, 
                'metode_pembayaran' => $this->metode_pembayaran,
                'status_pembayaran' => $status_awal, 
            ]);

            foreach ($this->keranjang as $item) {
                if ($item['tipe'] === 'racikan') {
                    if ($item['botol']['id'] !== null) {
                        Botol::where('id_botol', $item['botol']['id'])->decrement('stok_pcs', 1);
                    }
                    foreach ($item['parfums'] as $idx => $p) {
                        $beban_harga_botol = ($idx === 0) ? $item['botol']['harga'] : 0;
                        $harga_saat_ini = $p['subtotal'] + $beban_harga_botol;

                        $beban_modal_botol = ($idx === 0) ? $item['botol']['harga_beli'] : 0;
                        $modal_parfum = $p['harga_beli_per_ml'] * $p['ml'];
                        $subtotal_modal = $modal_parfum + $beban_modal_botol;
                        $harga_beli_satuan_gabungan = $p['harga_beli_per_ml'] + (($idx === 0) ? $item['botol']['harga_beli'] : 0);

                        DetailPenjualan::create([
                            'id_penjualan' => $penjualan->id_penjualan,
                            'id_botol' => $item['botol']['id'],
                            'id_parfum' => $p['id_parfum'],
                            'jumlah_ml' => $p['ml'],
                            'harga_saat_transaksi' => $harga_saat_ini,
                            'subtotal' => $harga_saat_ini,
                            'harga_beli_saat_transaksi' => $harga_beli_satuan_gabungan, 
                            'subtotal_modal' => $subtotal_modal,                         
                        ]);
                        Parfum::where('id_parfum', $p['id_parfum'])->decrement('stok_ml', $p['ml']);
                    }
                } 
                elseif ($item['tipe'] === 'jadi') {
                    $subtotal_modal_jadi = $item['harga_beli_satuan'] * $item['qty']; 

                    DetailPenjualan::create([
                        'id_penjualan' => $penjualan->id_penjualan,
                        'id_parfum_jadi' => $item['id_parfum_jadi'],
                        'jumlah_pcs' => $item['qty'],
                        'harga_saat_transaksi' => $item['harga_satuan'],
                        'subtotal' => $item['subtotal'],
                        'harga_beli_saat_transaksi' => $item['harga_beli_satuan'], 
                        'subtotal_modal' => $subtotal_modal_jadi,                  
                    ]);
                    ParfumJadi::where('id_parfum_jadi', $item['id_parfum_jadi'])->decrement('stok_pcs', $item['qty']);
                }
            }

            if ($this->metode_pembayaran === 'Tunai') {
                DB::commit();
                
                $this->nota_terakhir = $penjualan->no_faktur;
                $this->keranjang = [];
                $this->id_pelanggan = '';
                $this->subtotal_belanja = 0;
                $this->potongan_diskon = 0;
                $this->total_belanja = 0;
                $this->uang_dibayar = '';
                $this->kembalian = 0;
                
                session()->flash('success', 'Pembayaran Tunai Berhasil!');
                
            } else {
                Config::$serverKey = config('services.midtrans.serverKey');
                Config::$isProduction = config('services.midtrans.isProduction');
                Config::$isSanitized = true;
                Config::$is3ds = true;

                $params = [
                    'transaction_details' => [
                        'order_id' => $penjualan->no_faktur,
                        'gross_amount' => (int) $this->total_belanja,
                    ],
                    'customer_details' => [
                        'first_name' => $this->id_pelanggan ? Pelanggan::find($this->id_pelanggan)->nama_pelanggan : 'Pelanggan Umum',
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);
                $penjualan->update(['snap_token' => $snapToken]);

                DB::commit();

                $this->nota_terakhir = $penjualan->no_faktur;
                $this->keranjang = [];
                $this->id_pelanggan = '';
                $this->subtotal_belanja = 0;
                $this->potongan_diskon = 0;
                $this->total_belanja = 0;
                
                $this->pending_snap_token = $snapToken;
                $this->dispatch('pay-with-midtrans', token: $snapToken);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}; ?>

<div x-data="{ openJadiModal: false, openBotolModal: false, openBibitModal: false }">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-2xl font-bold dark:text-gray-100">Mesin Kasir</h2>
            <div class="text-right">
                <p class="text-sm text-gray-500">Kasir Aktif:</p>
                <p class="font-semibold dark:text-gray-100">{{ auth()->user()->name }}</p>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded flex justify-between items-center">
                <span>{{ session('success') }}</span>
                @if($nota_terakhir)
                    <a href="{{ route('kasir.struk', $nota_terakhir) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded text-sm shadow">
                        🖨️ Cetak Struk
                    </a>
                @endif
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif
        
        @if ($pending_snap_token)
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-5 py-4 rounded-lg flex flex-col md:flex-row justify-between items-center shadow-sm">
                <div class="mb-2 md:mb-0">
                    <strong class="block text-lg">⚠️ Menunggu Pembayaran Diselesaikan!</strong>
                    <span class="text-sm">Faktur <b>{{ $nota_terakhir }}</b> belum dibayar atau popup tertutup.</span>
                </div>
                <button wire:click="$dispatch('pay-with-midtrans', { token: '{{ $pending_snap_token }}' })" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded shadow transition-all">
                    Buka Ulang Payment Gateway
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            
            <div class="xl:col-span-2 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold mb-4 dark:text-gray-100 text-lg flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                Parfum Jadi (Ready)
                            </h3>
                            
                            @if (session()->has('error_jadi'))
                                <div class="text-red-600 text-sm mb-3 bg-red-50 p-2 rounded border border-red-200">
                                    {{ session('error_jadi') }}
                                </div>
                            @endif

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Produk Terpilih</label>
                                <div class="flex gap-2">
                                    <div class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 bg-zinc-50 dark:bg-zinc-900/40 flex items-center justify-between min-h-[42px]">
                                        @if($id_parfum_jadi_pilihan)
                                            @php $selectedPJ = $parfum_jadis->firstWhere('id_parfum_jadi', $id_parfum_jadi_pilihan); @endphp
                                            <span class="font-semibold text-sm dark:text-white">{{ $selectedPJ?->nama_parfum }}</span>
                                            <span class="text-xs font-bold text-purple-600 bg-purple-100 dark:bg-purple-950/40 dark:text-purple-400 px-2 py-0.5 rounded">Rp {{ number_format($selectedPJ?->harga_jual_per_pcs ?? 0, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400 italic text-sm">Belum ada produk dipilih</span>
                                        @endif
                                    </div>
                                    <button @click="openJadiModal = true" type="button" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-4 rounded-lg shadow-sm text-sm transition">
                                        🔍 Cari
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 items-end mt-2">
                            <div class="w-24">
                                <label class="block text-sm font-medium mb-1 dark:text-gray-200">Qty (Pcs)</label>
                                <input type="number" wire:model="qty_parfum_jadi" min="1" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 text-center focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <button wire:click="tambahParfumJadiKeKeranjang" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg transition-all shadow-sm">
                                + Masukkan Keranjang
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                        <h3 class="font-bold mb-4 dark:text-gray-100 text-lg flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Parfum Custom / Refill
                        </h3>
                        
                        @if (session()->has('error_racikan'))
                            <div class="text-red-600 text-sm mb-3 bg-red-50 p-2 rounded border border-red-200">
                                {{ session('error_racikan') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1 dark:text-gray-200">1. Wadah Botol</label>
                            <div class="flex gap-2">
                                <div class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 bg-zinc-50 dark:bg-zinc-900/40 flex items-center justify-between min-h-[42px]">
                                    @if($id_botol_pilihan === 'bawa_sendiri')
                                        <span class="font-semibold text-sm text-blue-600 dark:text-blue-400">🧴 Bawa Botol Sendiri (Refill)</span>
                                    @elseif($id_botol_pilihan)
                                        @php $selectedB = $botols->firstWhere('id_botol', $id_botol_pilihan); @endphp
                                        <span class="font-semibold text-sm dark:text-white">{{ $selectedB?->nama_botol }}</span>
                                        <span class="text-xs font-bold text-blue-600 bg-blue-100 dark:bg-blue-950/40 dark:text-blue-400 px-2 py-0.5 rounded">{{ $selectedB?->kapasitas_ml }} ML</span>
                                    @else
                                        <span class="text-gray-400 italic text-sm">Belum ada wadah dipilih</span>
                                    @endif
                                </div>
                                <button @click="openBotolModal = true" type="button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 rounded-lg shadow-sm text-sm transition">
                                    🧴 Pilih
                                </button>
                            </div>
                        </div>

                        @if($id_botol_pilihan)
                            <div class="flex gap-2 mb-4 items-end">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 dark:text-gray-200">2. Komposisi Bibit</label>
                                    <div class="flex gap-2">
                                        <div class="flex-1 border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 bg-zinc-50 dark:bg-zinc-900/40 flex items-center justify-between min-h-[42px]">
                                            @if($id_parfum_pilihan)
                                                @php $selectedP = $parfums->firstWhere('id_parfum', $id_parfum_pilihan); @endphp
                                                <span class="font-semibold text-xs dark:text-white truncate max-w-[120px]">{{ $selectedP?->nama_parfum }}</span>
                                            @else
                                                <span class="text-gray-400 italic text-sm">Pilih aromatik...</span>
                                            @endif
                                        </div>
                                        <button @click="openBibitModal = true" type="button" class="bg-zinc-700 hover:bg-zinc-600 dark:bg-zinc-900 dark:hover:bg-zinc-950 text-white font-semibold px-2.5 rounded-lg text-xs shadow-sm transition">
                                            💧 Cari
                                        </button>
                                    </div>
                                </div>
                                <div class="w-16">
                                    <label class="block text-sm font-medium mb-1 dark:text-gray-200">Takaran</label>
                                    <input type="number" wire:model="jumlah_ml_pilihan" placeholder="ML" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-2 py-2 text-sm text-center focus:ring-orange-500 focus:border-orange-500">
                                </div>
                                <button wire:click="tambahParfumKeRacikan" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-2 rounded-lg shadow-sm text-sm h-[42px]">Mix</button>
                            </div>

                            @if(count($racikan_sementara) > 0)
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-2 rounded-lg mb-3 text-xs">
                                    <div class="flex justify-between font-bold mb-1 dark:text-gray-300">
                                        @if($id_botol_pilihan === 'bawa_sendiri')
                                            <span>Total Refill: {{ $total_ml_racikan }} ML</span>
                                        @else
                                            <span>Terisi: {{ $total_ml_racikan }} / {{ $kapasitas_botol_pilihan }} ML</span>
                                        @endif
                                    </div>
                                    <ul class="divide-y divide-blue-200 dark:divide-blue-800">
                                        @foreach($racikan_sementara as $idx => $racikan)
                                            <li class="py-1 flex justify-between dark:text-gray-400">
                                                <span>{{ $racikan['nama_parfum'] }} ({{ $racikan['ml'] }}ML)</span>
                                                <button wire:click="hapusDariRacikan({{ $idx }})" class="text-red-500 font-bold">✕</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <button wire:click="masukkanKeKeranjang" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-bold text-sm shadow-sm transition-all">
                                    ✓ Simpan Racikan
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm">
                    <h3 class="font-bold mb-4 dark:text-gray-100 text-lg">Keranjang Belanjaan</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-zinc-100 dark:bg-zinc-900 border-b dark:border-zinc-700 dark:text-gray-200">
                                    <th class="px-4 py-3 text-left">Produk</th>
                                    <th class="px-4 py-3 text-center">Kapasitas/Qty</th>
                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($keranjang as $index => $item)
                                <tr class="border-b dark:border-zinc-700 dark:text-gray-300">
                                    <td class="px-4 py-3">
                                        @if($item['tipe'] === 'racikan')
                                            <div class="font-semibold text-blue-600 dark:text-blue-400">[Racikan] {{ $item['botol']['nama'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Isi: 
                                                @foreach($item['parfums'] as $p)
                                                    {{ $p['nama_parfum'] }} ({{ $p['ml'] }}ML){{ !$loop->last ? ' + ' : '' }}
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="font-semibold text-purple-600 dark:text-purple-400">[Ready] {{ $item['nama_produk'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['tipe'] === 'racikan')
                                            {{ $item['botol']['kapasitas'] }}
                                        @else
                                            {{ $item['qty'] }} Pcs
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="hapusDariKeranjang({{ $index }})" class="bg-red-100 text-red-600 hover:bg-red-200 px-3 py-1 rounded text-xs font-bold transition-colors">Hapus</button>
                                    </td>
                                </tr>
                                @endforeach
                                @if(count($keranjang) == 0)
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Belum ada barang di keranjang.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg border dark:border-zinc-700 shadow-sm h-fit sticky top-6">
                <h3 class="font-bold mb-4 dark:text-gray-100 text-lg border-b pb-2">Pembayaran</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 dark:text-gray-200">Pelanggan (Opsional)</label>
                    <select wire:model.live="id_pelanggan" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">-- Pelanggan Umum --</option>
                        @foreach($pelanggans as $plg)
                            <option value="{{ $plg->id_pelanggan }}">{{ $plg->nama_pelanggan }}</option>
                        @endforeach
                    </select>
                    
                    @if($ada_promo_member && empty($id_pelanggan))
                        <span class="text-xs text-red-500 font-semibold mt-1 block flex items-center gap-1">
                            ⚠️ Ada Promo Khusus Member! Pilih nama pelanggan untuk mengaktifkannya.
                        </span>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 dark:text-gray-200">Metode Pembayaran</label>
                    <select wire:model.live="metode_pembayaran" class="w-full border dark:border-zinc-600 dark:bg-zinc-900 dark:text-white rounded-lg px-3 py-2">
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="Qris">QRIS / E-Wallet</option>
                    </select>
                </div>

                @if($metode_pembayaran === 'Tunai')
                <div class="bg-zinc-50 dark:bg-zinc-900 border dark:border-zinc-700 p-5 rounded-lg mb-6 shadow-inner">
                    <label class="block text-sm font-medium mb-2 dark:text-gray-200">Uang Diterima dari Pelanggan (Rp)</label>
                    <input type="number" wire:model.live.debounce.300ms="uang_dibayar" 
                           class="w-full border dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-lg px-4 py-3 text-2xl font-bold text-right focus:ring-orange-500 focus:border-orange-500" 
                           placeholder="0">
                    
                    <div class="mt-5 flex justify-between items-center text-lg border-t dark:border-zinc-700 pt-4">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">Uang Kembalian:</span>
                        <span class="text-3xl font-black {{ ((int)$uang_dibayar >= $total_belanja) ? 'text-blue-600 dark:text-blue-500' : 'text-red-500' }}">
                            Rp {{ number_format($kembalian, 0, ',', '.') }}
                        </span>
                    </div>
                    
                    @if((int)$uang_dibayar > 0 && (int)$uang_dibayar < $total_belanja)
                        <p class="text-red-500 text-sm text-right mt-2 font-medium flex items-center justify-end gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                            Uang masih kurang Rp {{ number_format($total_belanja - (int)$uang_dibayar, 0, ',', '.') }}
                        </p>
                    @endif
                </div>
                @endif

                <div class="bg-zinc-100 dark:bg-zinc-900 p-5 rounded-lg mb-6 border dark:border-zinc-700">
                    <div class="flex justify-between items-center mb-2 text-gray-500 dark:text-gray-400">
                        <span class="text-sm">Subtotal Belanja</span>
                        <span class="font-medium">Rp {{ number_format($subtotal_belanja, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($diskon_aktif)
                        <div class="flex justify-between items-start mb-2 text-orange-500 dark:text-orange-400">
                            <span class="text-sm font-semibold">
                                Promo: {{ $diskon_aktif->nama_event }} 
                                @if($diskon_aktif->jenis_diskon === 'persentase') ({{ $diskon_aktif->nilai_diskon }}%) @endif
                                
                                @if($subtotal_belanja > 0 && $subtotal_belanja < $diskon_aktif->minimal_belanja)
                                    <br><small class="text-red-500 font-normal">*(Belum aktif, min. belanja Rp {{ number_format($diskon_aktif->minimal_belanja, 0, ',', '.') }})</small>
                                @endif
                            </span>
                            <span class="font-bold whitespace-nowrap">- Rp {{ number_format($potongan_diskon, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="border-t border-zinc-200 dark:border-zinc-700 my-3"></div>

                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Tagihan Akhir</p>
                        <p class="text-4xl font-black text-green-600 dark:text-green-500">Rp {{ number_format($total_belanja, 0, ',', '.') }}</p>
                    </div>
                </div>

                <button wire:click="simpanTransaksi" wire:confirm="Apakah pesanan dan total harga sudah sesuai?" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-sm transition-all text-lg">
                    Proses Pembayaran
                </button>
            </div>

        </div>
    </div>

    <div x-show="openJadiModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm" @click="openJadiModal = false"></div>
        <div class="relative bg-white dark:bg-zinc-900 border dark:border-zinc-800 rounded-xl max-w-lg w-full shadow-2xl p-5 z-10 flex flex-col gap-4 max-h-[80vh]">
            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-2">
                <h3 class="text-lg font-bold dark:text-white">Pilih Produk Parfum Jadi</h3>
                <button @click="openJadiModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
            </div>
            <div class="overflow-y-auto pr-1 flex flex-col gap-2">
                @foreach($parfum_jadis as $pj)
                    <div wire:click="$set('id_parfum_jadi_pilihan', '{{ $pj->id_parfum_jadi }}')" @click="openJadiModal = false" class="flex justify-between items-center p-3 border dark:border-zinc-800 rounded-xl cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-950/10 hover:border-orange-500 dark:hover:border-orange-500/50 transition group">
                        <div class="flex flex-col">
                            <span class="font-bold text-zinc-800 dark:text-white group-hover:text-orange-500 dark:group-hover:text-orange-400">{{ $pj->nama_parfum }}</span>
                            <span class="text-xs text-gray-500">Sisa Stok: {{ $pj->stok_pcs }} Pcs</span>
                        </div>
                        <span class="font-bold text-sm text-zinc-900 dark:text-zinc-200">Rp {{ number_format($pj->harga_jual_per_pcs, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="openBotolModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm" @click="openBotolModal = false"></div>
        <div class="relative bg-white dark:bg-zinc-900 border dark:border-zinc-800 rounded-xl max-w-lg w-full shadow-2xl p-5 z-10 flex flex-col gap-4 max-h-[80vh]">
            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-2">
                <h3 class="text-lg font-bold dark:text-white">Pilih Wadah Botol</h3>
                <button @click="openBotolModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
            </div>
            <div class="overflow-y-auto pr-1 flex flex-col gap-2">
                <div wire:click="pilihBotol('bawa_sendiri')" @click="openBotolModal = false" class="flex justify-between items-center p-3 border border-dashed border-orange-400 dark:border-orange-500/40 rounded-xl cursor-pointer bg-orange-50/20 dark:bg-orange-950/10 hover:bg-orange-50 dark:hover:bg-orange-950/20 transition group">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🧴</span>
                        <div class="flex flex-col">
                            <span class="font-bold text-orange-600 dark:text-orange-400">Bawa Botol Sendiri (Refill)</span>
                            <span class="text-xs text-gray-500">Pelanggan membawa wadahnya sendiri</span>
                        </div>
                    </div>
                    <span class="font-bold text-sm text-orange-600 dark:text-orange-400">Gratis</span>
                </div>

                @foreach($botols as $b)
                    <div wire:click="pilihBotol('{{ $b->id_botol }}')" @click="openBotolModal = false" class="flex justify-between items-center p-3 border dark:border-zinc-800 rounded-xl cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-950/10 hover:border-orange-500 dark:hover:border-orange-500/50 transition group">
                        <div class="flex flex-col">
                            <span class="font-bold text-zinc-800 dark:text-white group-hover:text-orange-500 dark:group-hover:text-orange-400">{{ $b->nama_botol }}</span>
                            <span class="text-xs text-gray-500">Kapasitas: {{ $b->kapasitas_ml }} ML | Stok: {{ $b->stok_pcs }} Pcs</span>
                        </div>
                        <span class="font-bold text-sm text-zinc-900 dark:text-zinc-200">Rp {{ number_format($b->harga_jual_per_pcs, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="openBibitModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm" @click="openBibitModal = false"></div>
        <div class="relative bg-white dark:bg-zinc-900 border dark:border-zinc-800 rounded-xl max-w-lg w-full shadow-2xl p-5 z-10 flex flex-col gap-4 max-h-[80vh]">
            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-2">
                <h3 class="text-lg font-bold dark:text-white">Pilih Aroma / Bibit Parfum</h3>
                <button @click="openBibitModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
            </div>
            <div class="overflow-y-auto pr-1 flex flex-col gap-2">
                @foreach($parfums as $p)
                    <div wire:click="$set('id_parfum_pilihan', '{{ $p->id_parfum }}')" @click="openBibitModal = false" class="flex justify-between items-center p-3 border dark:border-zinc-800 rounded-xl cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-950/10 hover:border-orange-500 dark:hover:border-orange-500/50 transition group">
                        <div class="flex flex-col">
                            <span class="font-bold text-zinc-800 dark:text-white group-hover:text-orange-500 dark:group-hover:text-orange-400">{{ $p->nama_parfum }}</span>
                            <span class="text-xs text-gray-500">Stok Gudang: {{ $p->stok_ml }} ML</span>
                        </div>
                        <span class="font-bold text-xs text-zinc-500 dark:text-zinc-400">Rp {{ number_format($p->harga_jual_per_ml, 0, ',', '.') }} / ML</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.clientKey') }}"></script>
<script>
    window.addEventListener('pay-with-midtrans', event => {
        window.snap.pay(event.detail.token, {
            onSuccess: function(result) { 
                alert("Pembayaran berhasil!");
                location.reload(); 
            },
            onPending: function(result) { 
                location.reload(); 
            },
            onError: function(result) { 
                alert("Pembayaran gagal atau dibatalkan!"); 
            },
            onClose: function() {
                alert("Anda menutup halaman pembayaran. Silakan klik tombol 'Buka Ulang Payment Gateway' jika ingin melanjutkan.");
            }
        });
    });
</script>