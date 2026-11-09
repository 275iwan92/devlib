<?php

/*
 * REMARK
 * Tujuan       : Garis waktu vertikal: peristiwa berurutan (terbaru di atas) dengan titik berwarna per jenis, judul, keterangan dan tanggal; bagian dari riwayat hubungan (customer, supplier, mesin).
 * Sumber hulu  : Komponen generik DevLib; data masuk lewat prop, tanpa tabel/model proyek.
 * Input        : items, empty, source.
 * Output       : Markup Blade inline (render() kelas ini).
 * Dipakai oleh : layar proyek lewat x-dl::timeline (mis. Customer 360); demo devlib.test > Status > Timeline.
 * Status       : Aktif (DevLib).
 */

namespace Devlib\Components;

// Garis waktu: $items = [['title', 'meta' (tanggal / keterangan kecil), 'body' (satu baris), 'tone' => accent|success|warn|danger|info|violet|neutral, 'icon' => fa-*, 'url' => tautan judul], ...].
// Urutan = urutan array (layar yang mengurutkan). Judul dan keterangan adalah data: tidak diterjemahkan.
class Timeline extends DevlibComponent
{
    public function __construct(
        public array $items = [],
        public string $empty = 'Nothing has happened yet.',
        public ?string $source = null,
    ) {}

    public function tag(): string
    {
        return 'x-dl::timeline';
    }

    public function source(): string
    {
        return $this->source ?? 'garis waktu';
    }

    public function render()
    {
        return <<<'BLADE'
            <div {{ $attributes->class('dl-timeline') }} {!! $dl() !!}>
                @forelse ($items as $i)
                    <div class="dl-timeline__item dl-timeline__item--{{ $i['tone'] ?? 'neutral' }}">
                        <span class="dl-timeline__dot">@if (! empty($i['icon']))<i class="fa-solid {{ \Devlib\Icon::fa($i['icon']) }}"></i>@endif</span>
                        <div class="dl-timeline__main">
                            <div class="dl-timeline__title">@if (! empty($i['url']))<a href="{{ $i['url'] }}">{{ $i['title'] ?? '' }}</a>@else{{ $i['title'] ?? '' }}@endif @if (! empty($i['meta']))<small class="dl-timeline__meta">{{ $i['meta'] }}</small>@endif</div>
                            @if (! empty($i['body']))<div class="dl-timeline__body">{{ $i['body'] }}</div>@endif
                        </div>
                    </div>
                @empty
                    <p class="dl-timeline__empty">{{ \Devlib\Lang::text($empty) }}</p>
                @endforelse
            </div>
            BLADE;
    }
}
