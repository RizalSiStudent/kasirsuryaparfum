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

    public $chart_parfum_labels = [];
    public $chart_parfum_data = [];

    public function mount()
    {
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        $penjualanBulanIni = Penjualan::where('status_pembayaran', 'success')
                                ->whereMonth('created_at', $bulanIni)
                                ->whereYear('created_at', $tahunIni);

        $this->total_pendapatan = (clone $penjualanBulanIni)->sum('total_bayar');
        $this->total_transaksi = (clone $penjualanBulanIni)->count();
        $this->total_produk_aktif = Parfum::count() + ParfumJadi::count();

        // Data Pendapatan Harian
        $tanggalAwal = Carbon::now()->subDays(6)->startOfDay();
        $tanggalAkhir = Carbon::now()->endOfDay();

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

        for ($i = 0; $i < 7; $i++) {
            $tgl_asli = Carbon::now()->subDays(6 - $i)->format('Y-m-d');
            $tgl_label = Carbon::parse($tgl_asli)->format('d M');
            
            $this->chart_labels[] = $tgl_label;
            $this->chart_data[] = isset($dataPenjualan[$tgl_asli]) ? $dataPenjualan[$tgl_asli]->total : 0;
        }

        // Data Top Parfum
        $topParfums = DB::table('detail_penjualans')
            ->join('penjualans', 'detail_penjualans.id_penjualan', '=', 'penjualans.id_penjualan')
            ->join('parfums', 'detail_penjualans.id_parfum', '=', 'parfums.id_parfum')
            ->select('parfums.nama_parfum', DB::raw('SUM(detail_penjualans.jumlah_ml) as total_ml'))
            ->where('penjualans.status_pembayaran', 'success')
            ->whereNotNull('detail_penjualans.id_parfum')
            ->groupBy('parfums.id_parfum', 'parfums.nama_parfum')
            ->orderByDesc('total_ml')
            ->limit(5)
            ->get();

        foreach ($topParfums as $tp) {
            $this->chart_parfum_labels[] = $tp->nama_parfum;
            $this->chart_parfum_data[] = (int) $tp->total_ml;
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
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendapatan Bulan Ini</p>
                    <h3 class="text-3xl font-black text-green-600 dark:text-green-500 mt-1">Rp {{ number_format($total_pendapatan, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full text-green-600 dark:text-green-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border dark:border-zinc-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Lunas</p>
                    <h3 class="text-3xl font-black text-blue-600 dark:text-blue-500 mt-1">{{ number_format($total_transaksi, 0, ',', '.') }} Nota</h3>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full text-blue-600 dark:text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>

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

        <div x-data="{ focus: null }" class="relative w-full h-[850px] lg:h-[480px] mt-2 rounded-xl bg-transparent">
            
            <div :class="
                    focus === null 
                        ? 'top-0 left-0 w-full lg:w-[calc(50%-12px)] h-[calc(50%-12px)] lg:h-full z-10' 
                        : (focus === 'penjualan' 
                            ? 'top-0 left-0 w-full lg:w-[calc(72%-12px)] h-[calc(70%-12px)] lg:h-full z-20 shadow-md ring-1 ring-zinc-200 dark:ring-zinc-700' 
                            : 'top-0 left-0 w-full lg:w-[calc(28%-12px)] h-[calc(30%-12px)] lg:h-full z-10 cursor-pointer opacity-75 hover:opacity-100 border border-green-500/40'
                        )
                 "
                 class="absolute bg-white dark:bg-zinc-800 p-4 sm:p-5 rounded-xl border dark:border-zinc-700 shadow-sm flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] group">
                
                <div class="flex justify-between items-center mb-2 sm:mb-4">
                    <h3 :class="(focus === 'parfum') ? 'text-xs sm:text-sm' : 'text-base sm:text-lg'" class="font-bold dark:text-gray-100 transition-all truncate text-green-600 dark:text-green-500">Grafik Pendapatan</h3>
                    
                    <button x-show="focus === null || focus === 'penjualan'" @click.stop="focus = focus === 'penjualan' ? null : 'penjualan'" class="p-1.5 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition text-gray-600 dark:text-gray-300">
                        <svg x-show="focus === null" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        <svg x-show="focus === 'penjualan'" style="display: none;" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h4v4m0-4l-5 5m15-1h-4v4m0-4l5 5M4 10h4V6m0 4l-5-5m15 1h-4V6m0 4l5-5"></path></svg>
                    </button>
                </div>
                
                <div class="relative w-full flex-1" wire:ignore>
                    <canvas id="grafikPenjualan"></canvas>
                </div>

                <div x-show="focus === 'parfum'" @click="focus = 'penjualan'" style="display: none;" class="absolute inset-0 z-30 flex items-center justify-center bg-zinc-900/5 hover:bg-zinc-900/20 transition-colors rounded-xl">
                    <span class="bg-black/80 text-white text-[10px] sm:text-xs px-3 py-1.5 rounded-full font-semibold shadow-lg backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity">Perbesar Grafik 📈</span>
                </div>
            </div>

            <div :class="
                    focus === null 
                        ? 'top-[calc(50%+12px)] lg:top-0 left-0 lg:left-[calc(50%+12px)] w-full lg:w-[calc(50%-12px)] h-[calc(50%-12px)] lg:h-full z-10' 
                        : (focus === 'penjualan' 
                            ? 'top-[calc(70%+12px)] lg:top-0 left-0 lg:left-[calc(72%+12px)] w-full lg:w-[calc(28%-12px)] h-[calc(30%-12px)] lg:h-full z-10 cursor-pointer opacity-75 hover:opacity-100 border border-orange-500/40' 
                            : 'top-[calc(30%+12px)] lg:top-0 left-0 lg:left-[calc(28%+12px)] w-full lg:w-[calc(72%-12px)] h-[calc(70%-12px)] lg:h-full z-20 shadow-md ring-1 ring-zinc-200 dark:ring-zinc-700'
                        )
                 "
                 class="absolute bg-white dark:bg-zinc-800 p-4 sm:p-5 rounded-xl border dark:border-zinc-700 shadow-sm flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] group">
                
                <div class="flex justify-between items-center mb-2 sm:mb-4">
                    <h3 :class="(focus === 'penjualan') ? 'text-xs sm:text-sm' : 'text-base sm:text-lg'" class="font-bold dark:text-gray-100 transition-all truncate text-orange-600 dark:text-orange-500">Top 5 Bibit Terlaris</h3>
                    
                    <button x-show="focus === null || focus === 'parfum'" @click.stop="focus = focus === 'parfum' ? null : 'parfum'" class="p-1.5 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition text-gray-600 dark:text-gray-300">
                        <svg x-show="focus === null" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        <svg x-show="focus === 'parfum'" style="display: none;" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h4v4m0-4l-5 5m15-1h-4v4m0-4l5 5M4 10h4V6m0 4l-5-5m15 1h-4V6m0 4l5-5"></path></svg>
                    </button>
                </div>
                
                <div class="relative w-full flex-1" wire:ignore>
                    <canvas id="grafikParfum"></canvas>
                </div>

                <div x-show="focus === 'penjualan'" @click="focus = 'parfum'" style="display: none;" class="absolute inset-0 z-30 flex items-center justify-center bg-zinc-900/5 hover:bg-zinc-900/20 transition-colors rounded-xl">
                    <span class="bg-black/80 text-white text-[10px] sm:text-xs px-3 py-1.5 rounded-full font-semibold shadow-lg backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity">Perbesar Grafik 📈</span>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function renderSemuaGrafik() {
            const canvasPenjualan = document.getElementById('grafikPenjualan');
            if (canvasPenjualan) {
                const ctxPenjualan = canvasPenjualan.getContext('2d');
                if (window.mySuryaChart) window.mySuryaChart.destroy();
                
                window.mySuryaChart = new Chart(ctxPenjualan, {
                    type: 'line',
                    data: {
                        labels: @json($chart_labels),
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: @json($chart_data),
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
                                    label: function(context) { return ' Rp ' + context.parsed.y.toLocaleString('id-ID'); }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(156, 163, 175, 0.1)' },
                                ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); } }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            const canvasParfum = document.getElementById('grafikParfum');
            if (canvasParfum) {
                const ctxParfum = canvasParfum.getContext('2d');
                if (window.myParfumChart) window.myParfumChart.destroy();
                
                window.myParfumChart = new Chart(ctxParfum, {
                    type: 'bar',
                    data: {
                        labels: @json($chart_parfum_labels),
                        datasets: [{
                            label: 'Terjual (ML)',
                            data: @json($chart_parfum_data),
                            backgroundColor: 'rgba(249, 115, 22, 0.85)',
                            borderColor: '#EA580C',
                            borderWidth: 1,
                            borderRadius: 6,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) { return ' Terjual: ' + context.parsed.y.toLocaleString('id-ID') + ' ML'; }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(156, 163, 175, 0.1)' },
                                ticks: { callback: function(value) { return value + ' ML'; } }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', renderSemuaGrafik);
        document.addEventListener('livewire:navigated', renderSemuaGrafik);
    </script>
</div>