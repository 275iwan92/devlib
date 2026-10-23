<?php

/*
 * REMARK
 * Tujuan       : Pembungkus grid dua kolom untuk x-dl::phone-tile di layar HP.
 * Sumber hulu  : Komponen generik DevLib.
 * Input        : slot.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar beranda HP proyek; halaman katalog p7-phone.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Grid dua kolom (jarak 12 px) untuk kartu x-dl::phone-tile; kartu ganjil terakhir tetap selebar satu kolom.
class PhoneTiles extends DevlibComponent
{
    public function tag(): string
    {
        return 'x-dl::phone-tiles';
    }

    public function source(): string
    {
        return '—';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-ptiles') }} {!! $dl() !!}>{{ $slot }}</div>
            BLADE;
    }
}
