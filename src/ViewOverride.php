<?php

namespace Devlib;

use Closure;
use Illuminate\Http\Request;

/* REMARK
 * Tujuan       : Pratinjau multi view: parameter `dl-view` (phone | desktop) dan `dl-os` (android | ios | other) menimpa view / OS untuk SATU permintaan, tanpa cookie.
 * Sumber hulu  : Device::override.
 * Input        : query dl-view, dl-os.
 * Output       : atribut permintaan dl.view / dl.os yang dibaca Device::view() dan Device::os().
 * Dipakai oleh : x-dl::view-as (iframe pratinjau). Opt-in: proyek memasangnya di grup web hanya bila mau menyediakan pratinjau (situs katalog, lingkungan QA).
 * Status       : Aktif (DevLib).
 */
// Hanya mengubah tampilan permintaan itu; tidak ada data, hak akses, atau cookie yang berubah. Tanpa parameter, middleware ini tidak berbuat apa-apa.
final class ViewOverride
{
    public function handle(Request $request, Closure $next)
    {
        $view = Device::normalize($request->query('dl-view'));
        if ($view !== null) {
            Device::override($request, $view, is_string($request->query('dl-os')) ? $request->query('dl-os') : null);
        }

        return $next($request);
    }
}
