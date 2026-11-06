<?php

/*
 * REMARK
 * Tujuan       : Tab: judul di atas (vertical = daftar di kiri, seperti halaman Setup & Security), isi berganti.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : tabs, active, vertical, switch, tones.
 * Output       : Markup Blade inline (render() kelas ini). switch=true: judul jadi satu baris pil (segmented switch); pil meluncur ke tab yang diklik (JS Tabs menulis --dl-thumb-x / --dl-thumb-w), warna pil dan latar panel mengikuti nada tab (tones: kunci tab => accent|success|warn|danger|violet|teal, atribut data-tone pada akar), dan panel berjajar sebagai carousel horizontal yang bergeser (--dl-tab-index).
 * Dipakai oleh : security, tabs-accordion (layar katalog/proyek), Master Product (switch).
 * Status       : Aktif (DevLib). switch dan tones = opt-in (bawaan mati; markup lama tidak berubah).
 */

namespace Devlib\Components;

// Tab: judul di atas (vertical = daftar di kiri, seperti halaman Setup & Security), isi berganti.
// Isi tiap tab = slot bernama sesuai kunci tab. tabs: [kunci => judul] atau [kunci => [judul, ikon]].
class Tabs extends DevlibComponent
{
    public function __construct(
        public array $tabs = [],
        public ?string $active = null,
        public bool $vertical = false,
        public bool $switch = false,
        public array $tones = [],
    ) {}

    public function tag(): string
    {
        return 'x-dl::tabs';
    }

    public function render()
    {
        return <<<'BLADE'
            @php
                $cur = $active ?? array_key_first($tabs);
                $idx = (int) array_search($cur, array_keys($tabs), true);
            @endphp
            <div {{ $attributes->class(['dl-tabs', 'is-vertical' => $vertical, 'dl-tabs--switch' => $switch]) }} {!! $dl() !!} data-dl-tabs @if ($switch) data-tone="{{ $tones[$cur] ?? 'accent' }}" style="--dl-tab-index:{{ $idx }}" @endif>
                <div class="dl-tabs__bar" role="tablist">
                    @if ($switch)<span class="dl-tabs__thumb" aria-hidden="true"></span>@endif
                    @foreach ($tabs as $key => $title)
                        <button type="button" class="dl-tabs__tab {{ $key === $cur ? 'is-active' : '' }}" data-tab="{{ $key }}" @if ($switch) data-tone="{{ $tones[$key] ?? 'accent' }}" @endif>@if (is_array($title))<i class="fa-solid {{ \Devlib\Icon::fa($title[1]) }}"></i> {{ $title[0] }}@else{{ $title }}@endif</button>
                    @endforeach
                </div>
                @if ($switch)<div class="dl-tabs__viewport"><div class="dl-tabs__track">@endif
                @foreach ($tabs as $key => $title)
                    <div class="dl-tabs__panel {{ $key === $cur ? 'is-active' : '' }}" data-panel="{{ $key }}">{{ ${$key} ?? '' }}</div>
                @endforeach
                @if ($switch)</div></div>@endif
            </div>
            BLADE;
    }
}
