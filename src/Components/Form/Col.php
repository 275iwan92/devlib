<?php

/*
 * REMARK
 * Tujuan       : Satu kolom form: field disusun ke bawah.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : (tanpa prop).
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : dirty-guard, generator (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Form;

use Devlib\Components\DevlibComponent;

// Satu kolom form: field disusun ke bawah.
class Col extends DevlibComponent
{
    public function tag(): string
    {
        return 'x-dl::form.col';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-form__col') }} {!! $dl() !!}>{{ $slot }}</div>
            BLADE;
    }
}
