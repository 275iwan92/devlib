<?php

/*
 * REMARK
 * Tujuan       : Ubin kategori lokasi yang bisa di-expand (lemari, gudang tertutup, gudang terbuka, area luas); isi = kartu lokasi, dan lokasi terpilih bisa membuka slot (denah).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop kinds, tanpa tabel/model proyek.
 * Input        : kinds (key, label, icon, tone, open, items[id,title,sub,href,active,map]), empty, slot (denah lokasi aktif).
 * Output       : Markup .dl-lk (details name=dl-lk, tanpa JS; CSS di devlib.css).
 * Dipakai oleh : x-dl::warehouse-desk (Warehouse Map).
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Ubin kategori ukuran sedang (4 kolom). Klik ubin = terbuka selebar baris: ubin mengecil jadi bilah, kartu lokasi di bawahnya;
// klik bilah terbuka = tutup; membuka kategori lain menutup yang lama (<details name>). Item aktif yang 'map' => true menampilkan
// $slot (denah) sebagai bagian yang bisa di-expand lagi.
//
// $kinds = [['key', 'label', 'icon', 'tone' => violet|teal|amber|coral|neutral, 'open' => bool,
//            'items' => [['id', 'title', 'sub', 'href', 'active' => bool, 'map' => bool], ...]], ...]
class LocationKinds extends DevlibComponent
{
    protected const TEXT_PROPS = ['empty'];

    public function __construct(
        public array $kinds = [],
        public string $empty = 'No location yet',
    ) {}

    public function tag(): string
    {
        return 'x-dl::location-kinds';
    }

    public function render()
    {
        return <<<'BLADE'
            @if (! $kinds){{ $slot }}@else
            <div {{ $attributes->class('dl-lk') }} {!! $dl() !!}>
                @foreach ($kinds as $k)
                    <details name="dl-lk" class="dl-lk__k dl-lk__k--{{ $k['tone'] ?? 'neutral' }}" @if (! empty($k['open'])) open @endif>
                        <summary class="dl-lk__sum">
                            <span class="dl-lk__ico"><i class="fa-solid {{ \Devlib\Icon::fa($k['icon'] ?? 'fa-box') }}"></i></span>
                            <span class="dl-lk__name">{{ \Devlib\Lang::text($k['label']) }}</span>
                            <span class="dl-lk__count">{{ count($k['items'] ?? []) }}</span>
                            <i class="fa-solid fa-chevron-up dl-lk__chev"></i>
                        </summary>
                        <div class="dl-lk__body">
                            @forelse ($k['items'] ?? [] as $i)
                                <a href="{{ $i['href'] }}" class="dl-lk__card @if (! empty($i['active'])) is-active @endif">
                                    <span class="dl-lk__title">{{ \Devlib\Lang::text($i['title']) }}</span>
                                    @if (! empty($i['sub']))<span class="dl-lk__sub">{{ \Devlib\Lang::text($i['sub']) }}</span>@endif
                                </a>
                                @if (! empty($i['active']) && ! empty($i['map']))
                                    <details class="dl-lk__map" open>
                                        <summary class="dl-lk__mapsum"><i class="fa-solid fa-map"></i> {{ \Devlib\Lang::text('Floor plan') }} · {{ \Devlib\Lang::text($i['title']) }} <i class="fa-solid fa-chevron-up dl-lk__chev"></i></summary>
                                        <div class="dl-lk__mapbody">{{ $slot }}</div>
                                    </details>
                                @endif
                            @empty
                                <span class="dl-lk__empty">{{ $empty }}</span>
                            @endforelse
                        </div>
                    </details>
                @endforeach
            </div>
            @endif
            BLADE;
    }
}
