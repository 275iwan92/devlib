<?php

/*
 * REMARK
 * Tujuan       : Bingkai pratinjau multi view. Mode slot: Laptop / Tablet (768px) / Smartphone (390px) dari mockup form. Mode `src`: halaman nyata di <iframe> berukuran perangkat (Android phone, iPhone, Tablet, Laptop, Desktop) dengan view phone / desktop yang sungguh dirender server.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek. Mode `src` membutuhkan Devlib\ViewOverride di grup web proyek.
 * Input        : mode, src, preset.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar proyek lewat x-dl::device-frame; halaman "Lihat sebagai" situs katalog.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Bingkai pratinjau. Tanpa `src` = tiga lebar atas slot (div, bukan emulator). Dengan `src` = iframe berukuran perangkat: @media dan tinggi layar di dalamnya mengikuti ukuran iframe, dan ?dl-view / ?dl-os
// membuat server merender view yang benar (lihat Devlib\ViewOverride).
class DeviceFrame extends DevlibComponent
{
    // key => [label, lebar CSS px, tinggi CSS px, view yang dirender, OS, ikon]. Ukuran acuan: docs/device-viewports.md.
    public const PRESETS = [
        'android' => ['Android phone', 360, 790, 'phone', 'android', 'fa-brands fa-android'],
        'ios' => ['iPhone', 393, 852, 'phone', 'ios', 'fa-brands fa-apple'],
        'tablet' => ['Tablet', 820, 1180, 'desktop', 'other', 'fa-solid fa-tablet-screen-button'],
        'laptop' => ['Laptop', 1366, 768, 'desktop', 'other', 'fa-solid fa-laptop'],
        'desktop' => ['Desktop', 1920, 1080, 'desktop', 'other', 'fa-solid fa-desktop'],
    ];

    public function __construct(
        public string $mode = 'laptop',
        // null = mode slot. Alamat halaman yang ditampilkan di iframe (satu halaman proyek, tanpa dl-view / dl-os; bingkai menambahkannya sendiri).
        public ?string $src = null,
        // Perangkat awal mode `src`: android | ios | tablet | laptop | desktop; nilai lain = android.
        public string $preset = 'android',
        // Mode `src`: alamat tautan "tutup" di ujung bilah (kembali ke halaman asli); null = tanpa tautan.
        public ?string $back = null,
    ) {
        if (! isset(self::PRESETS[$this->preset])) {
            $this->preset = 'android';
        }
    }

    public function tag(): string
    {
        return 'x-dl::device-frame';
    }

    // Alamat iframe untuk satu perangkat: src + ?dl-view=&dl-os= (menghormati query yang sudah ada).
    public function url(string $key): string
    {
        [, , , $view, $os] = self::PRESETS[$key];

        return $this->src.(str_contains((string) $this->src, '?') ? '&' : '?').http_build_query(['dl-view' => $view, 'dl-os' => $os]);
    }

    public function render()
    {
        return <<<'BLADE'
            @if ($src)
                @php([$label, $w, $h] = \Devlib\Components\DeviceFrame::PRESETS[$preset])
                <div {{ $attributes->class('dl-device dl-device--src') }} {!! $dl() !!} data-dl-device>
                    <div class="dl-device__bar">
                        <span class="dl-device__title">{{ \Devlib\Lang::text('View as') }}:</span>
                        @foreach (\Devlib\Components\DeviceFrame::PRESETS as $key => [$name, $pw, $ph, $pview, $pos, $icon])
                            <button type="button" class="dl-device__btn {{ $key === $preset ? 'is-active' : '' }}" data-preset="{{ $key }}" data-w="{{ $pw }}" data-h="{{ $ph }}" data-src="{{ $url($key) }}"><i class="{{ $icon }}" aria-hidden="true"></i> {{ \Devlib\Lang::text($name) }} <small>{{ $pw }}&times;{{ $ph }}</small></button>
                        @endforeach
                        @if ($back)<a class="dl-device__btn dl-device__close" href="{{ $back }}"><i class="fa-solid fa-xmark" aria-hidden="true"></i> {{ \Devlib\Lang::text('Close') }}</a>@endif
                    </div>
                    <div class="dl-device__stage" data-dl-device-stage>
                        <div class="dl-device__dev" data-dl-device-dev style="width:{{ $w }}px;height:{{ $h }}px"><iframe class="dl-device__frame" src="{{ $url($preset) }}" title="{{ \Devlib\Lang::text($label) }}"></iframe></div>
                    </div>
                </div>
            @else
                <div {{ $attributes->class('dl-device') }} {!! $dl() !!} data-dl-device>
                    <div class="dl-device__bar">
                        <span class="dl-device__title">{{ \Devlib\Lang::text('Display Mode') }}:</span>
                        <button type="button" class="dl-device__btn {{ $mode === 'laptop' ? 'is-active' : '' }}" data-mode="laptop"><span>🖥️</span> Laptop / Desktop (Full)</button>
                        <button type="button" class="dl-device__btn {{ $mode === 'tablet' ? 'is-active' : '' }}" data-mode="tablet"><span>📱</span> Tablet (768px)</button>
                        <button type="button" class="dl-device__btn {{ $mode === 'mobile' ? 'is-active' : '' }}" data-mode="mobile"><span>📱</span> Smartphone (390px)</button>
                    </div>
                    <div class="dl-device__stage"><div class="dl-device__view is-{{ $mode }}" data-dl-device-view>{{ $slot }}</div></div>
                </div>
            @endif
            BLADE;
    }
}
