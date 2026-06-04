<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                @php
                    $homeUrl = route('dashboard');
                    if(auth()->check()) {
                        if(auth()->user()->peran === 'pemilik') $homeUrl = route('pemilik.dashboard');
                        elseif(auth()->user()->peran === 'kasir') $homeUrl = route('kasir.penjualan');
                        elseif(auth()->user()->peran === 'admin_stok') $homeUrl = route('admin-stok.kelola');
                    }
                @endphp
                <x-app-logo :sidebar="true" href="{{ $homeUrl }}" wire:navigate />
                <flux:sidebar.collapse/>
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @if(auth()->check())
                    @php $peran = auth()->user()->peran; @endphp

                    {{-- MENU KHUSUS PEMILIK --}}
                    @if($peran === 'pemilik')
                        <flux:sidebar.group :heading="__('Platform')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('pemilik.dashboard')" :current="request()->routeIs('pemilik.dashboard')" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                        <flux:sidebar.group :heading="__('Barang Utama')" class="grid">
                            <flux:sidebar.item icon="beaker" :href="route('pemilik.parfum')" :current="request()->routeIs('pemilik.parfum')" wire:navigate>
                                {{ __('Data Parfum') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="sparkles" :href="route('pemilik.parfum-jadi')" :current="request()->routeIs('pemilik.parfum-jadi')" wire:navigate>
                                {{ __('Data Parfum Jadi') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="cube" :href="route('pemilik.botol')" :current="request()->routeIs('pemilik.botol')" wire:navigate>
                                {{ __('Data Botol') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="truck" :href="route('pemilik.supplier')" :current="request()->routeIs('pemilik.supplier')" wire:navigate>
                                {{ __('Data Supplier') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('admin-stok.kelola')" :current="request()->routeIs('admin-stok.kelola')" wire:navigate>
                                {{ __('Kelola Stok & Retur') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                        <flux:sidebar.group :heading="__('Pengguna')" class="grid">
                            <flux:sidebar.item icon="users" :href="route('pemilik.pelanggan')" :current="request()->routeIs('pemilik.pelanggan')" wire:navigate>
                                {{ __('Data Pelanggan') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="identification" :href="route('pemilik.karyawan')" :current="request()->routeIs('pemilik.karyawan')" wire:navigate>
                                {{ __('Kelola Karyawan') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                        <flux:sidebar.group :heading="__('Transaksi & Laporan')" class="grid">
                            <flux:sidebar.item icon="shopping-cart" :href="route('kasir.penjualan')" :current="request()->routeIs('kasir.penjualan')" wire:navigate>
                                {{ __('Mesin Kasir (POS)') }}
                            </flux:sidebar.item>
                        <flux:sidebar.item icon="ticket" :href="route('pemilik.diskon')" :current="request()->routeIs('pemilik.diskon')" wire:navigate>
        {{ __('Event Diskon') }}
    </flux:sidebar.item>
                            <flux:sidebar.item icon="chart-bar" :href="route('pemilik.laporan')" :current="request()->routeIs('pemilik.laporan')" wire:navigate>
                                {{ __('Laporan Penjualan') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                    {{-- MENU KHUSUS KASIR --}}
                    @elseif($peran === 'kasir')
                        <flux:sidebar.group :heading="__('Platform')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('kasir.dashboard')" :current="request()->routeIs('kasir.dashboard')" wire:navigate>
                                {{ __('Dashboard Kasir') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="shopping-cart" :href="route('kasir.penjualan')" :current="request()->routeIs('kasir.penjualan')" wire:navigate>
                                {{ __('Mesin Kasir (POS)') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                    {{-- MENU KHUSUS ADMIN STOK --}}
                    @elseif($peran === 'admin_stok')
                        <flux:sidebar.group :heading="__('Platform')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('admin-stok.dashboard')" :current="request()->routeIs('admin-stok.dashboard')" wire:navigate>
                                {{ __('Dashboard Gudang') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('admin-stok.kelola')" :current="request()->routeIs('admin-stok.kelola')" wire:navigate>
                                {{ __('Kelola Stok & Retur') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>