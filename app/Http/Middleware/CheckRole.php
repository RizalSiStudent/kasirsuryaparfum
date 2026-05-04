<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // $roles sekarang otomatis menjadi array ['kasir', 'pemilik']
        
        if (!auth()->check() || !in_array(auth()->user()->peran, $roles)) {
            abort(403, 'Akses Ditolak! Anda tidak memiliki izin untuk membuka halaman ini.');
        }

        return $next($request);
    }
}