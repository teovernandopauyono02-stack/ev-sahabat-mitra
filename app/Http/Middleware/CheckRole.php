<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CheckRole — Middleware untuk validasi role user.
 * Sistem ini single-admin, middleware ini dipakai sebagai pengaman tambahan.
 * Contoh penggunaan: Route::middleware(['auth', 'role:admin'])
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!empty($roles) && !in_array(Auth::user()->role, $roles)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
