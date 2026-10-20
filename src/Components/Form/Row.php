<?php

/*
 * REMARK
 * Tujuan       : Satu baris form: field berdampingan (default 2 sama lebar).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : cols.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : basic-locale, card-actionbar, doc-share, field-display dan 11 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components\Form;

use Devlib\Components\DevlibComponent;

// Satu baris form: field berdampingan (default 2 sama lebar).
class Row extends DevlibComponent
{
    public function __construct(
        public int $cols = 2,
    ) {}

    public function tag(): string
    {
        return 'x-dl::form.row cols='.$this->cols;
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-form__row') }} {!! $dl() !!} style="--dl-cols:{{ $cols }}">{{ $slot }}</div>
            BLADE;
    }
}
