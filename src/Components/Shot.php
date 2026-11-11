<?php

/*
 * REMARK
 * Tujuan       : Potongan tangkapan layar: gambar penuh diperbesar di sekitar satu titik (x%, y%) dengan cincin penanda berdenyut. Dipakai kartu, langkah artikel, dan slide Panduan Pengguna supaya tiap langkah punya gambar yang fokus.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : src, x, y, zoom, ring, ratio, alt, source.
 * Output       : Markup Blade inline (render() kelas ini); gaya di resources/assets/devlib.css (.dl-shot), tanpa JS.
 * Dipakai oleh : x-dl::chapters (kartu dan langkah), layar proyek lewat x-dl::shot; demo devlib.test > Feedback > Shot.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Shot: src = url gambar penuh; x, y = titik fokus (persen lebar/tinggi gambar); zoom = pembesaran (1 = utuh); ring = cincin berdenyut di titik;
// anim = url animasi (WebP/GIF loop, sudah terbingkai): ditampilkan sebagai ganti zoom + cincin; bila gerak dikurangi (prefers-reduced-motion) dipakai src statis; ratio = rasio bingkai ('16/10'); alt = teks pengganti (kosong = hiasan). Titik tidak bergeser saat diperbesar karena gambar diperbesar dari titik itu.
class Shot extends DevlibComponent
{
    public function __construct(
        public string $src = '',
        public float $x = 50,
        public float $y = 50,
        public float $zoom = 2.2,
        public bool $ring = true,
        public string $ratio = '16/10',
        public string $alt = '',
        public string $anim = '',
        public ?string $source = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::shot';
    }

    public function source(): string
    {
        return $this->source ?? 'tangkapan layar';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-shot') }} {!! $dl() !!} style="--ratio: {{ $ratio }}" @if ($alt === '') aria-hidden="true" @endif>
                @if ($anim !== '')
                    <picture><source media="(prefers-reduced-motion: reduce)" srcset="{{ $src }}"><img src="{{ $anim }}" alt="{{ $alt }}" style="--z: 1" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ $src }}'"></picture>
                @else
                <img src="{{ $src }}" alt="{{ $alt }}" style="transform-origin: {{ $x }}% {{ $y }}%; --z: {{ $zoom }}" loading="lazy" decoding="async">
                @endif
                @if ($ring && $anim === '')<i class="dl-shot__ring" style="left: {{ $x }}%; top: {{ $y }}%"></i>@endif
            </div>
            BLADE;
    }
}
