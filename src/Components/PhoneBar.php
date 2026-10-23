<?php

/*
 * REMARK
 * Tujuan       : Bilah tombol yang menempel di bawah area isi layar HP (di atas bilah tab): dua atau tiga tombol besar sebaris, selalu terlihat saat isi digulir.
 * Sumber hulu  : Komponen generik DevLib.
 * Input        : columns, slot (tombol).
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar HP proyek yang berurutan (mis. Sebelumnya / Berikutnya) di dalam x-dl::phone-shell.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Bilah aksi menempel di bawah (position: sticky) di dalam area isi x-dl::phone-shell. Isi = tombol (x-dl::button, tombol besar >= 52 px lebih nyaman).
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
