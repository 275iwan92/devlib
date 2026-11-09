<?php

/*
 * REMARK
 * Tujuan       : Dua (atau lebih) blok berdampingan yang otomatis bertumpuk di layar sempit (grid auto-fit 320px, jarak 12px).
 * Sumber hulu  : -
 * Input        : slot berisi blok-blok (kartu, gambar, field); ratio (opsional: 2:1, 1:2, 1).
 * Output       : <div class="dl-cols"> ... </div>; gaya .dl-cols di devlib.css.
 * Dipakai oleh : screens/inquiry-recalc, inquiry-revise, customer-order, master-card-collection.
 * Status       : Aktif.
 */

namespace Devlib\Components;

class Cols extends DevlibComponent
{
    // ratio: '' = auto-fit 320px; '2:1' | '1:2' = dua kolom berbanding 2:1 / 1:2 (satu kolom di layar sempit); '1' = satu kolom (tumpukan).
    public function __construct(public string $ratio = '') {}

    public function tag(): string
    {
        return 'x-dl::cols';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-cols') }} {!! $dl() !!} @if ($ratio) data-ratio="{{ $ratio }}" @endif>{{ $slot }}</div>
            BLADE;
    }
}
