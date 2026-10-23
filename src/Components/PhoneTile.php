<?php

/*
 * REMARK
 * Tujuan       : Kartu ringkasan layar HP: ikon, angka besar, dan nama; seluruh kartu adalah tautan (sasaran sentuh besar). Dua kartu per baris di dalam x-dl::phone-tiles.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : label, value, icon, url, tone.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar beranda HP proyek (di dalam x-dl::phone-shell); halaman katalog p7-phone.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Kartu angka untuk Beranda HP: satu kartu = satu tautan ke modulnya. tone: '' | info | warn | danger (latar dan garis).
// Susun dalam pembungkus x-dl::phone-tiles (grid dua kolom).
class PhoneTile extends DevlibComponent
{
    public function __construct(
        public string $label = '',
        public string|int|float|null $value = null,
        public string $icon = 'fa-solid fa-circle',
        public string $url = '#',
        public string $tone = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::phone-tile';
    }

    public function source(): string
    {
        return '—';
    }

    public function render()
    {
        return <<<'BLADE'
            <a {{ $attributes->class(['dl-ptile', 'dl-ptile--'.$tone => $tone !== '']) }} {!! $dl() !!} href="{{ $url }}">
                <i class="{{ $icon }}" aria-hidden="true"></i>
                @if ($value !== null)<strong>{{ $value }}</strong>@endif
                <span>{{ \Devlib\Lang::text($label) }}</span>
            </a>
            BLADE;
    }
}
