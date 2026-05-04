<?php

use Livewire\Volt\Component;
use App\Models\Penjualan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.print')] #[Title('Struk Pembelian')] class extends Component {
    public $penjualan;

    public function mount($no_faktur)
    {
        // Cari data penjualan berdasarkan nomor faktur, beserta semua relasinya
        $this->penjualan = Penjualan::with(['pengguna', 'pelanggan', 'detail.parfum', 'detail.botol', 'detail.parfumJadi'])
                            ->where('no_faktur', $no_faktur)
                            ->firstOrFail();
    }
}; ?>

<div class="bg-white w-full max-w-[80mm] p-4 shadow-lg text-xs print:shadow-none print:w-full">
    
    <div class="text-center border-b-2 border-dashed border-gray-400 pb-3 mb-3">
        <h2 class="text-lg font-bold uppercase tracking-widest">Surya Parfum</h2>
        <p class="text-gray-600">Jl. Contoh Alamat Toko No. 123<br>Telp: 0812-3456-7890</p>
    </div>

    <div class="mb-3 flex justify-between">
        <div>
            <p>No   : {{ $penjualan->no_faktur }}</p>
            <p>Kasir: {{ $penjualan->pengguna->name }}</p>
        </div>
        <div class="text-right">
            <p>{{ \Carbon\Carbon::parse($penjualan->waktu_transaksi)->format('d/m/y H:i') }}</p>
            <p>Plg  : {{ $penjualan->pelanggan ? $penjualan->pelanggan->nama_pelanggan : 'Umum' }}</p>
        </div>
    </div>

    <div class="border-t-2 border-b-2 border-dashed border-gray-400 py-2 mb-3">
        <table class="w-full text-left">
            <tbody>
                @foreach($penjualan->detail as $item)
                    <tr>
                        <td colspan="3" class="font-bold pt-2">
                            @if($item->id_parfum_jadi)
                                [Ready] {{ $item->parfumJadi->nama_parfum }}
                            @else
                                [Mix] {{ $item->parfum->nama_parfum }} ({{ $item->jumlah_ml }} ML)
                                <br><span class="font-normal text-gray-500">- Botol: {{ $item->botol ? $item->botol->nama_botol : 'Bawa Sendiri (Refill)' }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        @if($item->id_parfum_jadi)
                            <td>{{ $item->jumlah_pcs }} Pcs</td>
                            <td>x Rp{{ number_format($item->harga_saat_transaksi, 0, ',', '.') }}</td>
                        @else
                            <td>1 Paket</td>
                            <td>-</td>
                        @endif
                        <td class="text-right font-bold">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="text-right mb-4">
        <p class="text-sm">Total: <span class="font-bold text-lg">Rp{{ number_format($penjualan->total_bayar, 0, ',', '.') }}</span></p>
        <p class="text-gray-600">Metode: {{ $penjualan->metode_pembayaran }}</p>
    </div>

    <div class="text-center border-t-2 border-dashed border-gray-400 pt-3">
        <p class="font-bold">Terima Kasih!</p>
        <p class="text-gray-600">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>

    <div class="mt-6 text-center no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded font-bold w-full hover:bg-blue-700">
            🖨️ Cetak Struk
        </button>
        <a href="{{ route('kasir.penjualan') }}" wire:navigate class="block mt-2 text-blue-600 underline">Kembali ke Kasir</a>
    </div>

    <script>
        window.onload = function() { window.print(); }
    </script>
</div>