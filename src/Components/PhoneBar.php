<?php

/*
 * REMARK
 * Tujuan       : Bilah tombol besar sebaris di dalam isi layar HP (mis. Sebelumnya / Berikutnya): dua atau tiga tombol, ikut alur halaman.
 * Sumber hulu  : Komponen generik DevLib.
 * Input        : columns, slot (tombol).
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar HP proyek yang berurutan di dalam x-dl::phone-shell.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Bilah tombol berurutan di dalam isi x-dl::phone-shell; TIDAK menempel di bawah layar (bawah layar bebas kontrol, bentrok dengan tombol navigasi Android). Isi = tombol (x-dl::button, >= 52 px).
// Tombol diratakan menjadi kolom sama lebar; `columns` (nilai CSS grid-template-columns, mis. '1fr 1.5fr') mengatur lebar bila perlu.
class PhoneBar extends DevlibComponent
{
    public function __construct(
        public string $columns = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::phone-bar';
    }

    public function source(): string
    {
        return '—';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-pbar') }} {!! $dl() !!}@if ($columns !== '') style="grid-template-columns:{{ $columns }}"@endif>{{ $slot }}</div>
            BLADE;
    }
}
