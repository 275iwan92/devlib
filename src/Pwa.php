<?php

namespace Devlib;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

// Sisi server layar login PWA React (pwa/), opt-in: tidak ada rute atau perilaku baru sampai proyek memanggilnya.
// Logika login (tabel pengguna, hash, sesi) tetap milik proyek; di sini hanya bentuk balasannya.
class Pwa
{
    // Panggil di routes/web.php (grup web: sesi + CSRF): GET /pwa menyajikan hasil build, GET /pwa/session memberi {csrf, lang}.
    public static function routes(string $index = 'pwa/index.html'): void
    {
        Route::get('/pwa', fn () => response()->file(public_path($index)))->name('pwa');  // folder tidak disajikan sebagai index oleh peladen
        Route::get('/pwa/session', fn () => response()->json(['csrf' => csrf_token(), 'lang' => Locale::lang()]))->name('pwa.session');
    }

    // Login gagal: 422 {error: $code} bila klien minta JSON (empty | wrong), selain itu $html() (alur form biasa).
    public static function fail(Request $r, string $code, callable $html): Response
    {
        return $r->expectsJson() ? response()->json(['error' => $code], 422) : $html();
    }

    // Login berhasil: {redirect: alamat} bila klien minta JSON, selain itu $redirect apa adanya.
    public static function done(Request $r, RedirectResponse $redirect): Response
    {
        return $r->expectsJson() ? response()->json(['redirect' => $redirect->getTargetUrl()]) : $redirect;
    }
}
