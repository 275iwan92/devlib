<?php

/*
 * REMARK
 * Tujuan       : Stopwatch pribadi: hh:mm:ss.cc, Start/Stop, Lap, Reset, dan daftar putaran (lap) dengan selisih dari putaran sebelumnya. Berjalan murni di browser; keadaan diingat di localStorage sehingga benar setelah pindah layar atau muat ulang.
 * Sumber hulu  : Komponen generik DevLib; tanpa server dan tanpa tabel (sengaja sementara).
 * Input        : storageKey.
 * Output       : Markup Blade inline (render() kelas ini) + js/components/stopwatch.js + CSS .dl-sw.
 * Dipakai oleh : layar proyek lewat x-dl::stopwatch (menu Tools > Stopwatch).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Stopwatch interaktif. Beda dengan x-dl::timer (tampilan jam kerja dari detik server): ini milik pengguna, bisa dijalankan, dihentikan, dan dicatat putarannya.
// Keadaan localStorage[$storageKey] {running, startedAt (ms), base (ms terkumpul), laps [total ms]}; waktu dihitung dari jam, bukan dari hitungan detik.
class Stopwatch extends DevlibComponent
{
    public function __construct(public string $storageKey = 'dl-stopwatch') {}

    public function tag(): string
    {
        return 'x-dl::stopwatch';
    }

    public function source(): string
    {
        return 'localStorage '.$this->storageKey;
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-sw') }} {!! $dl() !!} data-dl-sw data-key="{{ $storageKey }}" data-l-start="{{ \Devlib\Lang::text('Start') }}" data-l-stop="{{ \Devlib\Lang::text('Stop') }}" data-l-lap="{{ \Devlib\Lang::text('Lap') }}">
                <output class="dl-sw__time" data-dl-sw-time aria-live="off">00:00:00.00</output>
                <div class="dl-sw__btns">
                    <button type="button" class="dl-btn dl-btn--primary" data-dl-sw-play><i class="fa-solid fa-play" aria-hidden="true"></i> <span>{{ \Devlib\Lang::text('Start') }}</span></button>
                    <button type="button" class="dl-btn dl-btn--ghost" data-dl-sw-lap>{{ \Devlib\Lang::text('Lap') }}</button>
                    <button type="button" class="dl-btn dl-btn--ghost" data-dl-sw-reset>{{ \Devlib\Lang::text('Reset') }}</button>
                </div>
                <ol class="dl-sw__laps" data-dl-sw-laps reversed></ol>
            </div>
            BLADE;
    }
}
