<?php

/*
 * REMARK
 * Tujuan       : Foto orang/objek; tanpa foto = inisial dengan warna tetap per nama.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : name, photo, size, sub.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : avatar-progress (layar katalog/proyek).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Foto orang/objek; tanpa foto = inisial dengan warna tetap per nama.
class Avatar extends DevlibComponent
{
    public function __construct(
        public string $name = '',
        public ?string $photo = null,
        public string $size = 'md',
        public ?string $sub = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::avatar';
    }

    public function initials(): string
    {
        $w = preg_split('/\s+/', trim($this->name)) ?: ['?'];

        return mb_strtoupper(mb_substr($w[0], 0, 1).(isset($w[1]) ? mb_substr($w[1], 0, 1) : ''));
    }

    public function hue(): int
    {
        return crc32($this->name) % 360;
    }

    public function render()
    {
        return <<<'BLADE'
            <span {{ $attributes->class(['dl-avatar-id', 'dl-avatar-id--'.$size]) }} {!! $dl() !!}>
                <span class="dl-avatar-id__pic" style="--h:{{ $hue() }}">@if ($photo)<img src="{{ $photo }}" alt="">@else{{ $initials() }}@endif</span>
                @if ($sub !== null)<span class="dl-avatar-id__txt"><b>{{ $name }}</b><small>{{ $sub }}</small></span>@endif
            </span>
            BLADE;
    }
}
