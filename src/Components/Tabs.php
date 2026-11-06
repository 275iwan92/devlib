<?php

/*
 * REMARK
 * Tujuan       : Tab: judul di atas (vertical = daftar di kiri, seperti halaman Setup & Security), isi berganti.
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : tabs, active, vertical, switch.
 * Output       : Markup Blade inline (render() kelas ini). switch=true: judul jadi satu baris pil (segmented switch) dengan pil aksen yang bergeser ke tab aktif (JS Tabs menaruh --dl-thumb-x / --dl-thumb-w).
 * Dipakai oleh : security, tabs-accordion (layar katalog/proyek), Master Product (switch).
 * Status       : Aktif (DevLib). switch = opt-in (bawaan false; markup lama tidak berubah).
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
    ) {}

    public function tag(): string
    {
        return 'x-dl::tabs';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class(['dl-tabs', 'is-vertical' => $vertical, 'dl-tabs--switch' => $switch]) }} {!! $dl() !!} data-dl-tabs>
                <div class="dl-tabs__bar" role="tablist">
                    @if ($switch)<span class="dl-tabs__thumb" aria-hidden="true"></span>@endif
                    @foreach ($tabs as $key => $title)
                        <button type="button" class="dl-tabs__tab {{ $key === ($active ?? array_key_first($tabs)) ? 'is-active' : '' }}" data-tab="{{ $key }}">@if (is_array($title))<i class="fa-solid {{ \Devlib\Icon::fa($title[1]) }}"></i> {{ $title[0] }}@else{{ $title }}@endif</button>
                    @endforeach
                </div>
                @foreach ($tabs as $key => $title)
                    <div class="dl-tabs__panel {{ $key === ($active ?? array_key_first($tabs)) ? 'is-active' : '' }}" data-panel="{{ $key }}">{{ ${$key} ?? '' }}</div>
                @endforeach
            </div>
            BLADE;
    }
}
