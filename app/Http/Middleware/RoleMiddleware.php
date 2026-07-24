<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Middleware untuk membatasi akses berdasarkan role.
     * Penggunaan di route: ->middleware('role:ketua_sppg,akuntan')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check() || ! in_array(auth()->user()->role, $roles)) {
            abort(403, 'Akses tidak diizinkan.');
        }

        return $next($request);
    }
}
