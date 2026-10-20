<?php

/*
 * REMARK
 * Tujuan       : Tanda sedang memproses. overlay=true menutupi induknya (mis. kartu/grid) selama proses.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : label, size, overlay.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : spinner (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Tanda sedang memproses. overlay=true menutupi induknya (mis. kartu/grid) selama proses.
class Spinner extends DevlibComponent
{
    public function __construct(
        public ?string $label = null,
        public string $size = 'md',
        public bool $overlay = false,
    ) {}

    public function tag(): string
    {
        return 'x-dl::spinner';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-spinner', 'dl-spinner--'.$size, 'dl-spinner--overlay' => $overlay]) }} {!! $dl() !!} role="status"><span class="dl-spinner__ring"></span>@if ($label)<span class="dl-spinner__label">{{ $label }}</span>@endif</div>
            BLADE;
    }
}
