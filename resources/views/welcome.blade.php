<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surya Parfum</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-orange-50 text-zinc-900 selection:bg-orange-200">
    <div class="min-h-screen flex flex-col items-center justify-center relative overflow-hidden">
        
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40"></div>
        <div class="absolute top-[20%] right-[-10%] w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-40"></div>

        <main class="w-full max-w-4xl px-6 py-12 relative z-10 text-center">
            
            <div class="flex justify-center mb-8">
                <img src="{{ asset('logo.jpeg') }}" alt="Surya Parfum Logo" class="h-28 w-auto drop-shadow-md transition-transform hover:scale-105 duration-300">
            </div>

            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4 text-zinc-800">
                Sistem Kasir & Inventaris <br>
                <span class="text-orange-500">Surya Parfum</span>
            </h1>
            
            <p class="text-lg text-zinc-600 mb-10 max-w-2xl mx-auto leading-relaxed">
                Kelola penjualan, pantau stok botol dan parfum, serta kelola data pelanggan dan supplier dengan lebih mudah, interaktif, dan cepat dalam satu platform.
            </p>

            @if (Route::has('login'))
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-8 py-3 rounded-full bg-orange-500 text-white font-semibold shadow-lg shadow-orange-500/30 hover:bg-orange-600 hover:shadow-orange-500/50 transition-all duration-300 transform hover:-translate-y-1">
                            Masuk ke Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-8 py-3 rounded-full bg-orange-500 text-white font-semibold shadow-lg shadow-orange-500/30 hover:bg-orange-600 hover:shadow-orange-500/50 transition-all duration-300 transform hover:-translate-y-1">
                            Log in Kasir / Admin
                        </a>
                    @endauth
                </div>
            @endif
        </main>

        <footer class="absolute bottom-6 text-sm text-zinc-500 font-medium tracking-wide">
            &copy; {{ date('Y') }} Surya Parfum. All rights reserved.
        </footer>
    </div>
</body>
</html>