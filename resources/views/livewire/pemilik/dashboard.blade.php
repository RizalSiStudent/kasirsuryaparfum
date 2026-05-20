<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Penjualan;
use App\Models\Parfum;
use App\Models\ParfumJadi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] #[Title('Dashboard - Surya Parfum')] class extends Component {
    
    // Variabel Ringkasan Metrik
    public $total_pendapatan = 0;
    public $total_transaksi = 0;
    public $total_produk_aktif = 0;

    // Variabel Grafik
    public $chart_labels = [];
    public $chart_data = [];

    public function mount()
    {
        // 1. Ambil Data Metrik Bulan Ini
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        $penjualanBulanIni = Penjualan::where('status_pembayaran', 'success')
                                ->whereMonth('created_at', $bulanIni)
                                ->whereYear('created_at', $tahunIni);

        $this->total_pendapatan = (clone $penjualanBulanIni)->sum('total_bayar');
        $this->total_transaksi = (clone $penjualanBulanIni)->count();
        $this->total_produk_aktif = Parfum::count() + ParfumJadi::count();

        // 2. Siapkan Data Grafik (7 Hari Terakhir)
        $tanggalAwal = Carbon::now()->subDays(6)->startOfDay();
        $tanggalAkhir = Carbon::now()->endOfDay();

        // Ambil rekap total penjualan harian dari database
        $dataPenjualan = Penjualan::select(
                            DB::raw('DATE(created_at) as tanggal'),
                            DB::raw('SUM(total_bayar) as total')
                        )
                        ->where('status_pembayaran', 'success')
                        ->whereBetween('created_at', [$tanggalAwal, $tanggalAkhir])
                        ->groupBy('tanggal')
                        ->orderBy('tanggal', 'asc')
                        ->get()
                        ->keyBy('tanggal');

        // Looping 7 hari ke belakang
        for ($i = 0; $i < 7; $i++) {
            $tgl_asli = Carbon::now()->subDays(6 - $i)->format('Y-m-d');
            $tgl_label = Carbon::parse($tgl_asli)->format('d M');
            
            $this->chart_labels[] = $tgl_label;
            $this->chart_data[] = isset($dataPenjualan[$tgl_asli]) ? $dataPenjualan[$tgl_asli]->total : 0;
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
        
        <div class="mb-2">
            <h2 class="text-2xl font-bold dark:text-gray-100">Beranda Pemilik</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan performa bisnis Surya Parfum saat ini.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Pendapatan -->
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendapatan Bulan Ini</p>
                    <h3 class="text-3xl font-black text-green-600 dark:text-green-500 mt-1">Rp {{ number_format($total_pendapatan, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full text-green-600 dark:text-green-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Transaksi -->
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Lunas (Bulan Ini)</p>
                    <h3 class="text-3xl font-black text-blue-600 dark:text-blue-500 mt-1">{{ number_format($total_transaksi, 0, ',', '.') }} Nota</h3>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full text-blue-600 dark:text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>

            <!-- Produk -->
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Varian Parfum</p>
                    <h3 class="text-3xl font-black text-purple-600 dark:text-purple-500 mt-1">{{ $total_produk_aktif }} Macam</h3>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full text-purple-600 dark:text-purple-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm mt-2" wire:ignore>
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg dark:text-gray-100">Tren Penjualan (7 Hari Terakhir)</h3>
            </div>
            
            <div class="relative h-80 w-full">
                <canvas id="grafikPenjualan"></canvas>
            </div>
        </div>

    </div>

    <!-- Pindahkan script ke bagian paling bawah dari komponen -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Fungsi utama untuk menggambar grafik
        function renderGrafik() {
            const canvas = document.getElementById('grafikPenjualan');
            
            // Pastikan canvas ada di halaman sebelum mencoba menggambar
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            // Tangkap data PHP yang dilempar Livewire
            const labels = @json($chart_labels);
            const dataPenjualan = @json($chart_data);

            // Jika sebelumnya sudah ada grafik yang tergambar, hancurkan dulu (mencegah error menumpuk)
            if (window.mySuryaChart) {
                window.mySuryaChart.destroy();
            }
            
            // Buat grafik baru
            window.mySuryaChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: dataPenjualan,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10B981',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(156, 163, 175, 0.1)' },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // EVENT 1: Dipicu saat halaman pertama kali diakses lewat F5 atau ketik URL
        document.addEventListener('DOMContentLoaded', renderGrafik);
        
        // EVENT 2: Dipicu khusus oleh Livewire v3 ketika pindah antar menu (SPA mode)
        document.addEventListener('livewire:navigated', renderGrafik);
    </script>
</div>