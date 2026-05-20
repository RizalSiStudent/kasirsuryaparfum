<x-layouts::auth>
    <div class="flex flex-col gap-6">
        
        <div class="flex justify-center -mt-2 mb-2">
            <img src="{{ asset('logo.jpeg') }}" alt="Surya Parfum" class="h-20 w-auto drop-shadow-sm transition-transform hover:scale-105 duration-300">
        </div>

        <x-auth-header :title="__('Selamat Datang Kembali')" :description="__('Silakan masuk ke akun Surya Parfum Anda')" />

        <x-auth-session-status class="text-center text-orange-600" :status="session('status')" />

        @if ($errors->any())
            <div class="flex items-start gap-3 p-4 border border-red-200 rounded-xl bg-red-50 shadow-sm">
                <div class="flex-shrink-0 p-1 bg-red-100 rounded-full mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-red-800 text-sm">Akses Ditolak</h3>
                    <p class="mt-1 text-xs text-red-700 leading-relaxed">
                        Email atau kata sandi yang Anda masukkan tidak sesuai. Silakan periksa kembali dan coba lagi.
                    </p>
                </div>
            </div>
            
@php
                view()->share('errors', new \Illuminate\Support\ViewErrorBag());
            @endphp
        @endif
        
        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                class="focus:border-orange-400 focus:ring-orange-200"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('********')"
                    viewable
                    class="focus:border-orange-400 focus:ring-orange-200"
                />
            </div>

            <flux:checkbox name="remember" :label="__('Ingat saya')" :checked="old('remember')" class="text-zinc-600 focus:ring-orange-500 text-orange-500" />

            <div class="flex flex-col gap-3 mt-2">
                <flux:button variant="primary" type="submit" class="w-full bg-orange-500 hover:bg-orange-600 border-none shadow-lg shadow-orange-500/20 py-3 font-bold transition-all duration-300 transform active:scale-95 text-white" data-test="login-button">
                    {{ __('Masuk Sekarang') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>