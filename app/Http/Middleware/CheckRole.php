<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Ambil nama role user yang login (secara aman)
        $userRole = Auth::user()->role?->nama;

        if (!$userRole) {
            abort(403, 'Anda tidak memiliki role yang didefinisikan.');
        }

        // Super Admin memiliki bypass akses ke semua route yang diproteksi CheckRole
        if ($userRole === 'Super Admin') {
            return $next($request);
        }

        // Cek apakah punya izin
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
    }

}