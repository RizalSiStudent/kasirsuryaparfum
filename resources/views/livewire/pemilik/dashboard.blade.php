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

    // Variabel Grafik Pendapatan Harian
    public $chart_pendapatan_labels = [];
    public $chart_pendapatan_data = [];

    // Variabel Grafik Top 5 Semua
    public $chart_top_all_labels = [];
    public $chart_top_all_data = [];

    // Variabel Grafik Top 5 Male
    public $chart_top_male_labels = [];
    public $chart_top_male_data = [];

    // Variabel Grafik Top 5 Female
    public $chart_top_female_labels = [];
    public $chart_top_female_data = [];

    public function mount()
    {
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;
        $hariDalamBulan = Carbon::now()->daysInMonth;

        // 1. Ringkasan Metrik
        $penjualanBulanIni = Penjualan::where('status_pembayaran', 'success')
                                ->whereMonth('created_at', $bulanIni)
                                ->whereYear('created_at', $tahunIni);

        $this->total_pendapatan = (clone $penjualanBulanIni)->sum('total_bayar');
        $this->total_transaksi = (clone $penjualanBulanIni)->count();
        $this->total_produk_aktif = Parfum::count() + ParfumJadi::count();

        // 2. Data Pendapatan Harian (Tanggal 1 s/d Akhir Bulan Ini)
        $dataPenjualanHarian = Penjualan::select(
                                DB::raw('DAY(created_at) as hari'),
                                DB::raw('SUM(total_bayar) as total')
                            )
                            ->where('status_pembayaran', 'success')
                            ->whereMonth('created_at', $bulanIni)
                            ->whereYear('created_at', $tahunIni)
                            ->groupBy('hari')
                            ->orderBy('hari', 'asc')
                            ->get()
                            ->keyBy('hari');

        for ($i = 1; $i <= $hariDalamBulan; $i++) {
            $this->chart_pendapatan_labels[] = (string) $i;
            $this->chart_pendapatan_data[] = isset($dataPenjualanHarian[$i]) ? $dataPenjualanHarian[$i]->total : 0;
        }

        // 3. Top 5 Bibit Keseluruhan
        $topOverall = $this->getTopBibitQuery()->limit(5)->get();
        foreach ($topOverall as $tp) {
            $this->chart_top_all_labels[] = Str::limit($tp->nama_parfum, 15);
            $this->chart_top_all_data[] = (int) $tp->total_ml;
        }

        // 4. Top 5 Bibit Pria (Male)
        $topMale = $this->getTopBibitQuery()->where('parfums.gender', 'Male')->limit(5)->get();
        foreach ($topMale as $tp) {
            $this->chart_top_male_labels[] = Str::limit($tp->nama_parfum, 15);
            $this->chart_top_male_data[] = (int) $tp->total_ml;
        }

        // 5. Top 5 Bibit Wanita (Female)
        $topFemale = $this->getTopBibitQuery()->where('parfums.gender', 'Female')->limit(5)->get();
        foreach ($topFemale as $tp) {
            $this->chart_top_female_labels[] = Str::limit($tp->nama_parfum, 15);
            $this->chart_top_female_data[] = (int) $tp->total_ml;
        }
    }

    // Fungsi Pembantu Agar Query Tidak Ditulis Berulang
    private function getTopBibitQuery()
    {
        return DB::table('detail_penjualans')
            ->join('penjualans', 'detail_penjualans.id_penjualan', '=', 'penjualans.id_penjualan')
            ->join('parfums', 'detail_penjualans.id_parfum', '=', 'parfums.id_parfum')
            ->select('parfums.nama_parfum', DB::raw('SUM(detail_penjualans.jumlah_ml) as total_ml'))
            ->where('penjualans.status_pembayaran', 'success')
            ->whereNotNull('detail_penjualans.id_parfum')
            ->groupBy('parfums.id_parfum', 'parfums.nama_parfum')
            ->orderByDesc('total_ml');
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

        <div x-data="{ zoomBawah: null }" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-2">
            
            <div class="bg-white dark:bg-zinc-800 p-5 rounded-xl border dark:border-zinc-700 shadow-sm flex flex-col lg:col-span-3 h-[350px]">
                <h3 class="text-lg font-bold dark:text-gray-100 text-green-600 dark:text-green-500 mb-2">
                    Pendapatan Harian (Bulan {{ now()->translatedFormat('F Y') }})
                </h3>
                <div class="relative w-full flex-1 min-h-0" wire:ignore>
                    <canvas id="grafikPendapatan"></canvas>
                </div>
            </div>

            <div x-show="zoomBawah === null || zoomBawah === 'all'" 
                 :class="zoomBawah === 'all' ? 'lg:col-span-3 h-[450px]' : 'h-[300px]'"
                 class="bg-white dark:bg-zinc-800 p-5 rounded-xl border dark:border-zinc-700 shadow-sm flex flex-col transition-all duration-300 overflow-hidden">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold dark:text-gray-100 text-orange-600 dark:text-orange-500 truncate" :class="zoomBawah === 'all' ? 'text-lg' : 'text-sm'">
                        🔥 Top 5 Semua Bibit
                    </h3>
                    <button @click="zoomBawah = zoomBawah === 'all' ? null : 'all'; setTimeout(() => window.dispatchEvent(new Event('resize')), 310)" class="p-1.5 bg-orange-50 dark:bg-orange-900/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 text-orange-600 rounded-lg transition-colors" title="Perbesar/Perkecil Grafik">
                        <svg x-show="zoomBawah !== 'all'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        <svg x-show="zoomBawah === 'all'" style="display: none;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h4v4m0-4l-5 5m15-1h-4v4m0-4l5 5M4 10h4V6m0 4l-5-5m15 1h-4V6m0 4l5-5"></path></svg>
                    </button>
                </div>
                <div class="relative w-full flex-1 min-h-0" wire:ignore>
                    <canvas id="grafikTopAll"></canvas>
                </div>
            </div>

            <div x-show="zoomBawah === null || zoomBawah === 'male'" 
                 :class="zoomBawah === 'male' ? 'lg:col-span-3 h-[450px]' : 'h-[300px]'"
                 class="bg-white dark:bg-zinc-800 p-5 rounded-xl border dark:border-zinc-700 shadow-sm flex flex-col transition-all duration-300 overflow-hidden">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold dark:text-gray-100 text-blue-600 dark:text-blue-500 truncate" :class="zoomBawah === 'male' ? 'text-lg' : 'text-sm'">
                        👨 Top 5 Male
                    </h3>
                    <button @click="zoomBawah = zoomBawah === 'male' ? null : 'male'; setTimeout(() => window.dispatchEvent(new Event('resize')), 310)" class="p-1.5 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-600 rounded-lg transition-colors" title="Perbesar/Perkecil Grafik">
                        <svg x-show="zoomBawah !== 'male'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        <svg x-show="zoomBawah === 'male'" style="display: none;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h4v4m0-4l-5 5m15-1h-4v4m0-4l5 5M4 10h4V6m0 4l-5-5m15 1h-4V6m0 4l5-5"></path></svg>
                    </button>
                </div>
                <div class="relative w-full flex-1 min-h-0" wire:ignore>
                    <canvas id="grafikTopMale"></canvas>
                </div>
            </div>

            <div x-show="zoomBawah === null || zoomBawah === 'female'" 
                 :class="zoomBawah === 'female' ? 'lg:col-span-3 h-[450px]' : 'h-[300px]'"
                 class="bg-white dark:bg-zinc-800 p-5 rounded-xl border dark:border-zinc-700 shadow-sm flex flex-col transition-all duration-300 overflow-hidden">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold dark:text-gray-100 text-pink-600 dark:text-pink-500 truncate" :class="zoomBawah === 'female' ? 'text-lg' : 'text-sm'">
                        👩 Top 5 Female
                    </h3>
                    <button @click="zoomBawah = zoomBawah === 'female' ? null : 'female'; setTimeout(() => window.dispatchEvent(new Event('resize')), 310)" class="p-1.5 bg-pink-50 dark:bg-pink-900/30 hover:bg-pink-100 dark:hover:bg-pink-900/50 text-pink-600 rounded-lg transition-colors" title="Perbesar/Perkecil Grafik">
                        <svg x-show="zoomBawah !== 'female'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                        <svg x-show="zoomBawah === 'female'" style="display: none;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14h4v4m0-4l-5 5m15-1h-4v4m0-4l5 5M4 10h4V6m0 4l-5-5m15 1h-4V6m0 4l5-5"></path></svg>
                    </button>
                </div>
                <div class="relative w-full flex-1 min-h-0" wire:ignore>
                    <canvas id="grafikTopFemale"></canvas>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function renderSemuaGrafik() {
            
            // Konfigurasi Standar untuk Chart Batang
           // Konfigurasi Standar untuk Chart Batang
            const barOptions = {
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
                        // Menampilkan kembali angka sumbu Y beserta keterangan "ML"
                        ticks: { 
                            display: true,
                            font: { size: 10 },
                            callback: function(value) { return value + ' ML'; }
                        },
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            };

            // 1. Grafik Pendapatan Harian
            const ctxPendapatan = document.getElementById('grafikPendapatan')?.getContext('2d');
            if (ctxPendapatan) {
                if (window.chartPendapatan) window.chartPendapatan.destroy();
                window.chartPendapatan = new Chart(ctxPendapatan, {
                    type: 'line',
                    data: {
                        labels: @json($chart_pendapatan_labels),
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: @json($chart_pendapatan_data),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.15)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) { return ' Rp ' + context.parsed.y.toLocaleString('id-ID'); },
                                    title: function(context) { return 'Tgl ' + context[0].label; }
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

            // 2. Grafik Top All
            const ctxTopAll = document.getElementById('grafikTopAll')?.getContext('2d');
            if (ctxTopAll) {
                if (window.chartTopAll) window.chartTopAll.destroy();
                window.chartTopAll = new Chart(ctxTopAll, {
                    type: 'bar',
                    data: {
                        labels: @json($chart_top_all_labels),
                        datasets: [{
                            data: @json($chart_top_all_data),
                            backgroundColor: 'rgba(249, 115, 22, 0.85)',
                            borderColor: '#EA580C',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6
                        }]
                    },
                    options: barOptions
                });
            }

            // 3. Grafik Top Male
            const ctxTopMale = document.getElementById('grafikTopMale')?.getContext('2d');
            if (ctxTopMale) {
                if (window.chartTopMale) window.chartTopMale.destroy();
                window.chartTopMale = new Chart(ctxTopMale, {
                    type: 'bar',
                    data: {
                        labels: @json($chart_top_male_labels),
                        datasets: [{
                            data: @json($chart_top_male_data),
                            backgroundColor: 'rgba(59, 130, 246, 0.85)', // Biru
                            borderColor: '#2563EB',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6
                        }]
                    },
                    options: barOptions
                });
            }

            // 4. Grafik Top Female
            const ctxTopFemale = document.getElementById('grafikTopFemale')?.getContext('2d');
            if (ctxTopFemale) {
                if (window.chartTopFemale) window.chartTopFemale.destroy();
                window.chartTopFemale = new Chart(ctxTopFemale, {
                    type: 'bar',
                    data: {
                        labels: @json($chart_top_female_labels),
                        datasets: [{
                            data: @json($chart_top_female_data),
                            backgroundColor: 'rgba(236, 72, 153, 0.85)', // Pink
                            borderColor: '#DB2777',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6
                        }]
                    },
                    options: barOptions
                });
            }
        }

        document.addEventListener('DOMContentLoaded', renderSemuaGrafik);
        document.addEventListener('livewire:navigated', renderSemuaGrafik);
    </script>
</div>