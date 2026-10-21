<?php

/*
 * REMARK
 * Tujuan       : Kotak pesan sebaris. Kosong = tersembunyi (bisa diisi lewat JS). Slot = isi kaya (teks tebal + tombol aksi).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : tone, message.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : basic-themes, error-pages, misc, permissions dan 6 lainnya (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Kotak pesan sebaris. Kosong = tersembunyi (bisa diisi lewat JS). Slot = isi kaya (teks tebal + tombol aksi).
class Alert extends DevlibComponent
{
    public function __construct(
        public string $tone = 'danger', // danger | info | warn | success
        public ?string $message = null,
        public array $items = [], // butir pesan (daftar poin di bawah message), mis. semua galat validasi form
    ) {}

    public function tag(): string
    {
        return 'x-dl::alert tone='.$this->tone;
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-alert', 'dl-alert--'.$tone, 'is-show' => $message || $items || trim($slot) !== '']) }} {!! $dl() !!} data-dl-alert>{{ $message }}@if ($items)<ul class="dl-alert__list">@foreach ($items as $item)<li>{{ $item }}</li>@endforeach</ul>@endif{{ $slot }}</div>
            BLADE;
    }
}
