<?php

/*
 * REMARK
 * Tujuan       : Pengumuman bergantian (dari file teks lewat x-dl::frame announce="...").
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : lines, interval.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar proyek lewat x-dl::announce.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Pengumuman bergantian (dari file teks lewat x-dl::frame announce="...").
class Announce extends DevlibComponent
{
    public function __construct(public array $lines = [], public int $interval = 6) {}

    public function tag(): string
    {
        return 'x-dl::announce';
    }

    public function source(): string
    {
        return 'media/text/pengumuman.txt';
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($lines)
                <div {{ $attributes->class('dl-announce') }} {!! $dl() !!} data-dl-announce data-lines="{{ json_encode(array_column($lines, 't')) }}" data-interval="{{ $interval }}"><i class="fa-solid fa-bullhorn"></i><span data-dl-announce-text>{{ $lines[0]['t'] }}</span></div>
            @endif
            BLADE;
    }
}
