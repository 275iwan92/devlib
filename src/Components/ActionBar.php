<?php

/*
 * REMARK
 * Tujuan       : Baris tombol di kaki form/kartu. align: end (default) | start | between.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : align.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : card-actionbar, doc-share, print-doc-a4, print-doc-a5 dan 5 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Baris tombol di kaki form/kartu. align: end (default) | start | between.
class ActionBar extends DevlibComponent
{
    public function __construct(
        public string $align = 'end',
    ) {}

    public function tag(): string
    {
        return 'x-dl::action-bar';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-actionbar', 'dl-actionbar--'.$align]) }} {!! $dl() !!}>{{ $slot }}</div>
            BLADE;
    }
}
