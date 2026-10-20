<?php

/*
 * REMARK
 * Tujuan       : Ikon baku: sebut ARTINYA (save, delete, print, ...), bukan nama Font Awesome.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, label.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : basic-icons (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Ikon baku: sebut ARTINYA (save, delete, print, ...), bukan nama Font Awesome.
class Icon extends DevlibComponent
{
    public function __construct(public string $name = 'info', public ?string $label = null) {}

    public function tag(): string
    {
        return 'x-dl::icon name='.$this->name;
    }

    public function fa(): string
    {
        return \Devlib\Icon::fa($this->name);
    }

    public function render()
    {
        return <<<'BLADE'
            <i {{ $attributes->class(['fa-solid', $fa(), 'dl-icon']) }} {!! $dl() !!} @if ($label) title="{{ $label }}" aria-label="{{ $label }}" @else aria-hidden="true" @endif></i>
            BLADE;
    }
}
