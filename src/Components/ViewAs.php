<?php

/*
 * REMARK
 * Tujuan       : Saklar "Lihat sebagai": pil melayang dengan menu perangkat (Android phone, iPhone, Tablet, Laptop, Desktop); memilih satu membuka halaman pratinjau berisi x-dl::device-frame mode `src`.
 * Sumber hulu  : Devlib\Components\DeviceFrame::PRESETS (daftar dan ukuran perangkat); rute pratinjau milik proyek.
 * Input        : href, label.
 * Output       : <details> tanpa JavaScript; tiap pilihan = tautan href?as=<perangkat>.
 * Dipakai oleh : halaman peraga katalog DevLib; proyek yang menyediakan pratinjau multi view.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Saklar pratinjau multi view. Hanya menautkan: halaman tujuan (rute proyek) membaca ?as= dan menampilkan x-dl::device-frame :src dengan preset itu.
class ViewAs extends DevlibComponent
{
    public function __construct(
        // Alamat halaman pratinjau; ?as=<android|ios|tablet|laptop|desktop> ditambahkan pada tiap pilihan.
        public string $href,
        public string $label = 'View as',
    ) {}

    public function tag(): string
    {
        return 'x-dl::view-as';
    }

    public function link(string $key): string
    {
        return $this->href.(str_contains($this->href, '?') ? '&' : '?').'as='.$key;
    }

    public function render()
    {
        return <<<'BLADE'
            <details {{ $attributes->class('dl-viewas') }} {!! $dl() !!}>
                <summary class="dl-viewas__btn" title="{{ \Devlib\Lang::text($label) }}" aria-label="{{ \Devlib\Lang::text($label) }}"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i></summary>
                <nav class="dl-viewas__menu">
                    <span class="dl-viewas__t">{{ \Devlib\Lang::text($label) }}</span>
                    @foreach (\Devlib\Components\DeviceFrame::PRESETS as $key => [$name, $w, $h, , , $icon])
                        <a href="{{ $link($key) }}"><i class="{{ $icon }}" aria-hidden="true"></i> {{ \Devlib\Lang::text($name) }} <small>{{ $w }}&times;{{ $h }}</small></a>
                    @endforeach
                </nav>
            </details>
            BLADE;
    }
}
