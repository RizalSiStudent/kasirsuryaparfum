<?php

use Livewire\Volt\Component;
use App\Models\Penjualan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.print')] #[Title('Struk Penjualan - Surya Parfum')] class extends Component {
    public Penjualan $penjualan;

    public function mount($no_faktur)
    {
        // Ambil data penjualan beserta relasinya
        $this->penjualan = Penjualan::with(['details.parfum', 'details.botol', 'details.parfumJadi', 'pengguna', 'pelanggan'])
            ->where('no_faktur', $no_faktur)
            ->firstOrFail();
    }
}; ?>

<div>
    <div class="max-w-[320px] mx-auto bg-white p-5 text-black font-mono text-sm leading-tight shadow-xl my-10 print:shadow-none print:m-0 print:p-0 print:max-w-full">
        
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold uppercase tracking-widest mb-1">Surya Parfum</h1>
            <p class="text-xs text-gray-700">Pusat Parfum Refill & Original</p>
            <p class="text-xs text-gray-700">IG: @suryaparfum | WA: 0812-XXXX-XXXX</p>
            <div class="border-b border-dashed border-gray-400 mt-3 mb-2"></div>
        </div>

        <div class="mb-4 text-xs">
            <div class="flex justify-between mb-1">
                <span>No.</span>
                <span class="font-bold">{{ $penjualan->no_faktur }}</span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Tgl</span>
                <span>{{ \Carbon\Carbon::parse($penjualan->created_at)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between mb-1">
                <span>Ksr</span>
                <span>{{ $penjualan->pengguna->name }}</span>
            </div>
            <div class="flex justify-between">
                <span>Plg</span>
                <span>{{ $penjualan->pelanggan ? $penjualan->pelanggan->nama_pelanggan : 'Umum' }}</span>
            </div>
            <div class="border-b border-dashed border-gray-400 mt-2 mb-2"></div>
        </div>

        <div class="mb-4">
            <table class="w-full text-xs">
                <tbody class="align-top">
                    @php
                        $current_botol_id = null;
                    @endphp

                    @foreach($penjualan->details as $detail)
                        @if($detail->id_parfum_jadi)
                            <tr>
                                <td class="pt-2 font-bold" colspan="3">{{ $detail->parfumJadi->nama_parfum }}</td>
                            </tr>
                            <tr>
                                <td class="pb-1 text-gray-600 pl-2">{{ $detail->jumlah_pcs }}x {{ number_format($detail->harga_saat_transaksi, 0, ',', '.') }}</td>
                                <td class="pb-1 text-right font-bold" colspan="2">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @php $current_botol_id = null; @endphp
                        @else
                            @php
                                $harga_bibit_per_ml = $detail->parfum ? $detail->parfum->harga_jual_per_ml : 0;
                                $subtotal_bibit = $harga_bibit_per_ml * $detail->jumlah_ml;
                                $selisih_botol = $detail->subtotal - $subtotal_bibit;
                            @endphp

                            @if($selisih_botol > 0)
                                <tr>
                                    <td class="pt-2 font-bold" colspan="3"> {{ $detail->botol ? $detail->botol->nama_botol . ' ' . $detail->botol->kapasitas_ml . 'ml' : 'Custom' }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-2 text-gray-600">- Harga Botol (1x)</td>
                                    <td class="text-right" colspan="2">{{ number_format($selisih_botol, 0, ',', '.') }}</td>
                                </tr>
                                @php $current_botol_id = $detail->id_botol; @endphp
                            @endif

                            <tr>
                                <td class="{{ ($current_botol_id && $current_botol_id == $detail->id_botol) ? 'pl-2 text-gray-600' : 'pt-2 font-bold' }}">
                                    {{ ($current_botol_id && $current_botol_id == $detail->id_botol) ? '- Bibit ' : '' }}{{ $detail->parfum ? $detail->parfum->nama_parfum : 'Parfum Refill' }}
                                </td>
                                <td class="text-center whitespace-nowrap {{ ($current_botol_id && $current_botol_id == $detail->id_botol) ? '' : 'pt-2' }}">{{ $detail->jumlah_ml }}ml</td>
                                <td class="text-right font-bold {{ ($current_botol_id && $current_botol_id == $detail->id_botol) ? '' : 'pt-2' }}">{{ number_format($subtotal_bibit, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <div class="border-b border-dashed border-gray-400 mt-3 mb-2"></div>
        </div>

        <div class="mb-5 text-sm">
            @if($penjualan->potongan_diskon > 0)
                <div class="flex justify-between mb-1 text-gray-700 text-xs">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($penjualan->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between mb-1 text-gray-700 text-xs">
                    <span>Diskon / Promo</span>
                    <span>- Rp {{ number_format($penjualan->potongan_diskon, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="flex justify-between mb-2 font-bold text-base">
                <span>TOTAL</span>
                <span>Rp {{ number_format($penjualan->total_bayar, 0, ',', '.') }}</span>
            </div>

            <div class="border-b border-dashed border-gray-400 mt-2 mb-2"></div>

            <div class="flex justify-between text-xs font-normal text-gray-600 mb-1">
                <span>Metode</span>
                <span class="uppercase font-bold text-black">{{ $penjualan->metode_pembayaran }}</span>
            </div>

            @if($penjualan->metode_pembayaran === 'Tunai')
                <div class="flex justify-between text-xs font-normal text-gray-600 mb-1">
                    <span>Tunai</span>
                    <span class="text-black">Rp {{ number_format($penjualan->uang_dibayar, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs font-normal text-gray-600 mb-1">
                    <span>Kembali</span>
                    <span class="text-black">Rp {{ number_format($penjualan->kembalian, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="flex justify-between text-xs font-normal text-gray-600">
                <span>Status</span>
                <span class="uppercase font-bold {{ $penjualan->status_pembayaran === 'success' ? 'text-black' : 'text-red-500' }}">
                    {{ $penjualan->status_pembayaran === 'success' ? 'LUNAS' : $penjualan->status_pembayaran }}
                </span>
            </div>
        </div>

        <div class="text-center text-xs mt-6 mb-2 text-gray-700">
            <p>*** Terima Kasih ***</p>
            <p class="mt-1">Kualitas Wangi Adalah Prioritas Kami</p>
        </div>
        
        <div class="mt-8 flex flex-col gap-2 print:hidden">
            <button onclick="window.print()" class="w-full bg-black text-white px-4 py-3 rounded-lg font-bold shadow hover:bg-gray-800 transition-colors">
                🖨️ Cetak Struk
            </button>
            <button onclick="window.close()" class="w-full bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                Tutup Halaman
            </button>
        </div>
    </div>

    <style>
        /* Styling khusus agar background website tidak ikut ke-print */
        @media print {
            body { background-color: white !important; }
            @page { margin: 0; size: 58mm auto; }
        }
    </style>
</div>