<?php

/*
 * REMARK
 * Tujuan       : Ikon baku: sebut ARTINYA (save, delete, print, ...), bukan nama Font Awesome.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, label, set (opt-in: line4).
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : basic-icons (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Ikon baku: sebut ARTINYA (save, delete, print, ...), bukan nama Font Awesome.
class Icon extends DevlibComponent
{
    public function __construct(public string $name = 'info', public ?string $label = null, public string $set = '') {}

    public function tag(): string
    {
        return 'x-dl::icon name='.$this->name;
    }

    public function fa(): string
    {
        return \Devlib\Icon::fa($this->name);
    }

    // Set opt-in: set="line4" dan artinya ada di Devlib\IconLine4 = SVG garis 4px; selain itu Font Awesome.
    public function line4(): bool
    {
        return $this->set === 'line4' && \Devlib\IconLine4::has($this->name);
    }

    public function svg(): string
    {
        return \Devlib\IconLine4::svg($this->name);
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($line4())
            <span {{ $attributes->class(['dl-icon-line4']) }} {!! $dl() !!} @if ($label) title="{{ $label }}" aria-label="{{ $label }}" role="img" @else aria-hidden="true" @endif>{!! $svg() !!}</span>
            @else
            <i {{ $attributes->class(['fa-solid', $fa(), 'dl-icon']) }} {!! $dl() !!} @if ($label) title="{{ $label }}" aria-label="{{ $label }}" @else aria-hidden="true" @endif></i>
            @endif
            BLADE;
    }
}
