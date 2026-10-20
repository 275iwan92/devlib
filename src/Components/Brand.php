<?php

namespace Devlib\Components;

/* REMARK
 * Tujuan: lencana logo + nama + tagline aplikasi. tone: light (di atas gelap) | dark | auto.
 * Sumber hulu: prop dari pemanggil; identity() = identitas bawaan situs (config devlib.brand.*, lalu DevLib).
 * Input: name, tagline, tone, logo (url gambar; kosong = lencana gradasi bawaan).
 * Output: div.dl-brand (lencana + nama + tagline).
 * Dipakai oleh: Frame (login), Shell / LayerShell (sidebar), LoginBox, layar identitas aplikasi.
 * Status: aktif.
 */
class Brand extends DevlibComponent
{
    /** Identitas bawaan situs: config('devlib.brand.*') bila diisi, selain itu DevLib (tiap proyek mengisi config devlib.brand, atau mengikat 'devlib.brand' dari tabel konstanta). */
    public static function identity(): array
    {
        // Aplikasi yang namanya disimpan di database (tabel konstanta) cukup mengikat 'devlib.brand' => fn () => ['name', 'tagline', 'logo'].
        $bound = app()->bound('devlib.brand') ? app('devlib.brand') : [];

        return [
            'name' => $bound['name'] ?? config('devlib.brand.name', 'DEVLIB'),
            'tagline' => $bound['tagline'] ?? config('devlib.brand.tagline', 'developer library'),
            'logo' => $bound['logo'] ?? config('devlib.brand.logo'),
        ];
    }

    public function __construct(
        public ?string $name = null,
        public ?string $tagline = null,
        public string $tone = 'light',
        public ?string $logo = null,
    ) {
        $identity = self::identity();
        $this->name ??= $identity['name'];
        $this->tagline ??= $identity['tagline'];
    }

    public function tag(): string
    {
        return 'x-dl::brand tone='.$this->tone;
    }

    public function source(): string
    {
        return 'identitas perusahaan (aplikasi: Settings > Identifikasi)';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-brand', 'dl-brand--'.$tone]) }} {!! $dl() !!}><span class="dl-logo-badge">@if ($logo)<img src="{{ $logo }}" alt="{{ $name }}">@endif</span><div><b>{{ $name }}</b><span>{{ $tagline }}</span></div></div>
            BLADE;
    }
}
