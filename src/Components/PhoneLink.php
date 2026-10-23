<?php

/*
 * REMARK
 * Tujuan       : Baris kartu besar layar HP: ikon, nama, lencana kecil; seluruh baris adalah tautan (tinggi >= 64 px). Untuk daftar modul / menu pada layar HP.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : label, icon, url, badge, tone.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar daftar modul dan pengingat HP proyek (di dalam x-dl::phone-shell); halaman katalog p7-phone.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Satu baris kartu tautan untuk layar HP. tone: '' | info | warn | danger (latar dan garis; untuk pengingat).
class PhoneLink extends DevlibComponent
{
    public function __construct(
        public string $label = '',
        public string $icon = 'fa-solid fa-circle',
        public string $url = '#',
        public ?string $badge = null,
        public string $tone = '',
    ) {}

    public function tag(): string
    {
        return 'x-dl::phone-link';
    }

    public function source(): string
    {
        return '—';
    }

    public function render()
    {
        return <<<'BLADE'
            <a {{ $attributes->class(['dl-plink', 'dl-plink--'.$tone => $tone !== '']) }} {!! $dl() !!} href="{{ $url }}">
                <i class="{{ $icon }}" aria-hidden="true"></i>
                <span>{{ \Devlib\Lang::text($label) }}</span>
                @if ($badge)<em>{{ $badge }}</em>@endif
            </a>
            BLADE;
    }
}
