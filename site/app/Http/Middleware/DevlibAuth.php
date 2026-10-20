<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Gerbang login tanpa database: cukup ada devlib_user di sesi.
class DevlibAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->has('devlib_user')) {
            // Login situs ini hanya contoh tampilan (bukan pengamanan): bawaan masuk otomatis sebagai akun contoh pertama (DEVLIB_LOGIN_BYPASS).
            if (config('devlib.login_bypass')) {
                $request->session()->put('devlib_user', collect(config('devlib.users')[0])->except('password')->all());
            } else {
                return redirect()->guest(route('login'));
            }
        }

        return $next($request);
    }
}
